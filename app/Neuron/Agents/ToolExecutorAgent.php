<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use App\Neuron\Providers\MinimaxAnthropic;
use NeuronAI\Agent\Agent;
use NeuronAI\HttpClient\GuzzleHttpClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;
use Symfony\Component\Process\Process;

class ToolExecutorAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        $key = (string) config('neuron.anthropic.key');
        $baseUri = (string) config('neuron.anthropic.base_uri');
        $model = (string) config('neuron.anthropic.model');

        // Fail fast with a clear error; command layer should decide offline vs online.
        if ($key === '' || $baseUri === '' || $model === '') {
            throw new \InvalidArgumentException('Missing Neuron provider configuration (neuron.anthropic.*).');
        }

        // Tool-calling requests can take longer than the default 60s Guzzle timeout.
        // Use 180s to allow the LLM time to process complex tool definitions.
        $httpClient = new GuzzleHttpClient(timeout: 180.0, connectTimeout: 15.0);

        return new MinimaxAnthropic(
            key: $key,
            model: $model,
            baseUriOverride: $baseUri,
            maxTokens: 4096,
            parameters: [
                'temperature' => 0.2,
            ],
            httpClient: $httpClient,
        );
    }

    protected function instructions(): string
    {
        return implode("\n", [
            '語言規定：所有輸出一律使用繁體中文。禁止輸出英文或任何推理思考過程，直接執行工具並在最後輸出結果摘要。',
            '',
            '你是 ToolExecutorAgent，負責在 repo 可允許的範圍內套用變更，跑測試，通過才 commit/push。',
            '',
            '操作流程（依序執行）：',
            '1. 使用 find_files 定位需要修改的檔案，再用 read_file 讀取，避免盲目掃描整個 repo。',
            '2. 用 write_file 套用修改（每次修改後不重複讀取同一檔案）。',
            '3. 若有 PHP 程式碼變更，執行 run_pint 格式化。',
            '4. 執行 run_tests 跑測試。',
            '5. 測試 PASS → 呼叫 http_healthcheck 確認首頁正常回應（HTTP 200 且含有效 HTML）。',
            '6. 健康檢查 PASS → 呼叫 git_commit_push 提交並推送。',
            '7. 測試或健康檢查 FAIL → 根據錯誤訊息修正後重跑，直到全數通過或達到重試上限。',
            '',
            '【最高警戒：開機路徑檔案】',
            '若修改了以下任何一個「開機路徑」檔案，必須在 run_tests PASS 後立即執行 http_healthcheck，',
            '健康檢查通過才能 commit/push。違反此規則等同任務失敗：',
            '- bootstrap/app.php',
            '- bootstrap/providers.php',
            '- public/index.php',
            '- config/*.php（任何設定檔）',
            '- app/Http/Middleware/*.php（任何 middleware）',
            '- app/Providers/*.php（任何 ServiceProvider）',
            '- composer.json / composer.lock',
            '',
            '規則：',
            '- 僅使用工具完成操作，不要輸出需要人類手動套用的 diff。',
            '- 不重複讀取同一檔案；不讀取與任務無關的檔案。',
            '- 不得修改 .env 或 .env.* 檔案。',
            '',
            '最終輸出（繁體中文摘要）：',
            '- 套用了哪些檔案（列出路徑）',
            '- 測試結果 PASS/FAIL',
            '- HTTP 健康檢查結果',
            '- commit hash（若有）',
            '- 推送狀態',
        ]);
    }

    /**
     * @return array<ToolInterface>
     */
    protected function tools(): array
    {
        return [
            $this->findFilesTool(),
            $this->readFileTool(),
            $this->writeFileTool(),
            $this->runPintTool(),
            $this->runTestsTool(),
            $this->httpHealthcheckTool(),
            $this->gitCommitPushTool(),
        ];
    }

    private function allowedWritePath(string $relativePath): bool
    {
        $path = ltrim($relativePath, '/');

        // Basic traversal guard
        if (str_contains($path, '..')) {
            return false;
        }

        // Still prevent writing real environment secrets by default.
        // (User asked to cancel whitelist; this is a safety exception.)
        if ($path === '.env' || str_starts_with($path, '.env.')) {
            return false;
        }

        return true;
    }

    private function allowedReadPath(string $relativePath): bool
    {
        $path = ltrim($relativePath, '/');

        if (str_contains($path, '..')) {
            return false;
        }

        // Read .env secrets is also blocked.
        if ($path === '.env' || str_starts_with($path, '.env.')) {
            return false;
        }

        return true;
    }

    private function runProcess(string $command, int $timeoutSeconds = 600): array
    {
        $process = Process::fromShellCommandline($command, base_path());
        $process->setTimeout($timeoutSeconds);
        $process->run();

        return [
            'exitCode' => $process->getExitCode(),
            'success' => $process->isSuccessful(),
            'stdout' => (string) $process->getOutput(),
            'stderr' => (string) $process->getErrorOutput(),
        ];
    }

    private function findFilesTool(): ToolInterface
    {
        return Tool::make(
            name: 'find_files',
            description: '以 glob 模式列出 repo 內符合條件的檔案路徑（相對於 repo 根）。用於在 read_file 之前定位目標檔案，避免盲目掃描。範例：app/**/*.php、.ai-dev/**/*.md、tests/**/*Test.php。',
            properties: [
                ToolProperty::make('pattern', PropertyType::STRING, 'glob 模式（相對於 repo 根，例如 app/Neuron/**/*.php）。', true),
            ],
        )->setMaxRuns(20)->setCallable(function (string $pattern): string {
            if (str_contains($pattern, '..')) {
                return 'ERROR: pattern contains path traversal.';
            }

            $base = base_path();
            $absPattern = $base.'/'.ltrim($pattern, '/');

            // PHP glob doesn't support ** recursive; use RecursiveDirectoryIterator for that.
            if (str_contains($pattern, '**')) {
                $parts = explode('**/', $pattern, 2);
                $baseDir = $base.'/'.ltrim($parts[0], '/');
                $subPattern = $parts[1] ?? '*';

                if (! is_dir($baseDir)) {
                    return 'ERROR: directory does not exist: '.$parts[0];
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                $matches = [];
                foreach ($iterator as $file) {
                    if ($file->isFile() && fnmatch($subPattern, $file->getFilename())) {
                        $matches[] = str_replace($base.'/', '', $file->getPathname());
                    }
                }

                sort($matches);

                return $matches === [] ? 'NO_FILES_FOUND' : implode("\n", array_slice($matches, 0, 100));
            }

            $files = glob($absPattern, GLOB_BRACE) ?: [];
            $relative = array_map(fn (string $f): string => str_replace($base.'/', '', $f), $files);

            sort($relative);

            return $relative === [] ? 'NO_FILES_FOUND' : implode("\n", array_slice($relative, 0, 100));
        });
    }

    private function readFileTool(): ToolInterface
    {
        return Tool::make(
            name: 'read_file',
            description: '讀取專案檔案內容（路徑以 repo 根為相對路徑；預設阻擋讀取 `.env` 類敏感設定）。',
            properties: [
                ToolProperty::make('path', PropertyType::STRING, '相對於 repo 根的檔案路徑（例如 .ai-dev/development/README.md）。', true),
            ],
        )->setMaxRuns(30)->setCallable(function (string $path): string {
            if (! $this->allowedReadPath($path)) {
                return 'ERROR: path is not allowed for reading.';
            }

            $abs = base_path(ltrim($path, '/'));
            if (! is_file($abs)) {
                return 'ERROR: file does not exist.';
            }

            $content = (string) file_get_contents($abs);

            return $content === '' ? 'ERROR: file content is empty.' : $content;
        });
    }

    private function writeFileTool(): ToolInterface
    {
        return Tool::make(
            name: 'write_file',
            description: '在 repo 內寫入檔案內容（路徑以 repo 根為相對路徑；預設阻擋寫入 `.env` 類敏感設定）。',
            properties: [
                ToolProperty::make('path', PropertyType::STRING, '相對於 repo 根的檔案路徑（例如 .ai-dev/development/README.md）。', true),
                // NOTE: Neuron 可能在某些情況下未帶上 content；此處避免工具執行直接爆 MissingCallbackParameter，
                // 讓 callable 內改回傳「缺少 content」錯誤供 Orchestrator/人類判斷並重試。
                ToolProperty::make('content', PropertyType::STRING, '要寫入的檔案內容（UTF-8）。', false),
            ],
        )->setMaxRuns(30)->setCallable(function (string $path, ?string $content = null): string {
            if (! $this->allowedWritePath($path)) {
                return 'ERROR: path is not allowed for writing.';
            }

            if ($content === null) {
                return 'ERROR: missing write_file content.';
            }

            $abs = base_path(ltrim($path, '/'));
            $dir = dirname($abs);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            file_put_contents($abs, $content);

            return 'OK: file written.';
        });
    }

    private function runPintTool(): ToolInterface
    {
        return Tool::make(
            name: 'run_pint',
            description: '執行 vendor/bin/pint --dirty，用於套用程式碼格式（若本次只有修改 Markdown 可能不會有實質變更）。',
            properties: [],
        )->setMaxRuns(10)->setCallable(function (): string {
            $result = $this->runProcess('vendor/bin/pint --dirty', 600);

            return json_encode([
                'exitCode' => $result['exitCode'],
                'success' => $result['success'],
                'stdoutTail' => mb_substr($result['stdout'], -3000),
                'stderrTail' => mb_substr($result['stderr'], -3000),
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    private function runTestsTool(): ToolInterface
    {
        return Tool::make(
            name: 'run_tests',
            description: '執行 php artisan test --compact，回傳結果。',
            properties: [],
        )->setMaxRuns(10)->setCallable(function (): string {
            $result = $this->runProcess('php artisan test --compact', 900);

            return json_encode([
                'exitCode' => $result['exitCode'],
                'success' => $result['success'],
                'stdoutTail' => mb_substr($result['stdout'], -4000),
                'stderrTail' => mb_substr($result['stderr'], -4000),
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    private function httpHealthcheckTool(): ToolInterface
    {
        return Tool::make(
            name: 'http_healthcheck',
            description: '對本機應用程式首頁發送 HTTP GET 請求，確認回應為 200 且含有效 HTML（<!DOCTYPE html>）。修改開機路徑檔案（bootstrap/app.php、config/*.php、middleware、ServiceProvider 等）後必須呼叫此工具，確認應用程式仍可正常啟動。',
            properties: [],
        )->setMaxRuns(5)->setCallable(function (): string {
            $url = config('app.url', 'https://timesheet-saas.test');
            $result = $this->runProcess(
                'curl -s -m 10 -o /tmp/hc_response.html -w "%{http_code}" '.escapeshellarg($url).'/',
                30
            );

            $statusCode = (int) trim($result['stdout']);
            $body = is_file('/tmp/hc_response.html') ? (string) file_get_contents('/tmp/hc_response.html') : '';

            if ($statusCode !== 200) {
                return json_encode([
                    'status' => 'FAIL',
                    'httpCode' => $statusCode,
                    'bodyHead' => mb_substr($body, 0, 500),
                ], JSON_UNESCAPED_UNICODE);
            }

            $hasValidHtml = str_contains(strtolower($body), '<!doctype html>') || str_contains(strtolower($body), '<html');

            if (! $hasValidHtml) {
                return json_encode([
                    'status' => 'FAIL',
                    'httpCode' => $statusCode,
                    'reason' => '回應不含有效 HTML（疑似 PHP fatal error）',
                    'bodyHead' => mb_substr($body, 0, 500),
                ], JSON_UNESCAPED_UNICODE);
            }

            return json_encode([
                'status' => 'PASS',
                'httpCode' => $statusCode,
                'reason' => '首頁正常回應且含有效 HTML',
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    private function gitCommitPushTool(): ToolInterface
    {
        return Tool::make(
            name: 'git_commit_push',
            description: '若目前 repo 內有變更：git add、commit、並 push 到 origin（推送到目前分支）。若無變更則回傳 NO_CHANGES。',
            properties: [
                ToolProperty::make('message', PropertyType::STRING, 'commit message', true),
            ],
        )->setMaxRuns(3)->setCallable(function (string $message): string {
            // Stage all changes (excluding unstaged local changes is not handled here).
            // Note: write_file tool still blocks writing to real `.env` by default.
            $this->runProcess('git add -A', 120);

            // No staged changes?
            $diff = $this->runProcess('git diff --cached --name-only', 120);
            $names = trim($diff['stdout']);
            if ($names === '') {
                return 'NO_CHANGES';
            }

            // Ensure git user is available; if missing, commit will fail and agent can retry.
            $branch = trim($this->runProcess('git branch --show-current', 30)['stdout']);

            $commit = $this->runProcess('git commit -m '.escapeshellarg($message), 120);
            if (! $commit['success']) {
                return 'ERROR: commit failed. '.mb_substr($commit['stderr'], -2000);
            }

            $hash = trim($this->runProcess('git rev-parse HEAD', 30)['stdout']);

            $push = $this->runProcess('git push origin '.escapeshellarg($branch), 300);
            if (! $push['success']) {
                return 'COMMITTED_BUT_PUSH_FAILED: '.$hash;
            }

            return 'COMMITTED_AND_PUSHED: '.$hash;
        });
    }
}
