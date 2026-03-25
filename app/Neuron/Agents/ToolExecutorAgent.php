<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use App\Neuron\Providers\MinimaxAnthropic;
use NeuronAI\Agent\Agent;
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

        return new MinimaxAnthropic(
            key: $key,
            model: $model,
            baseUriOverride: $baseUri,
            maxTokens: 900,
            parameters: [
                'temperature' => 0.2,
            ],
        );
    }

    protected function instructions(): string
    {
        return implode("\n", [
            '你是 ToolExecutorAgent，負責在白名單允許的範圍內套用變更，跑測試，通過才 commit/push。',
            '',
            '規則：',
            '- 僅使用工具完成操作，不要直接在輸出文字中附帶需要人類手動套用的 diff。',
            '- 僅修改允許的路徑（詳見 read_file/write_file 工具的限制；你不得要求突破限制）。',
            '- 在任何檔案修改後，先執行 `vendor/bin/pint --dirty`（若適用），再執行 `php artisan test --compact`。',
            '- 若測試 FAIL：請根據測試輸出反推原因並繼續修正，再重跑測試。',
            '- 若測試 PASS：呼叫 git_commit_push 工具 commit/push。',
            '',
            '輸出（給人類/Orchestrator）：',
            '- 最後請用繁體中文摘要：套用哪些檔案、測試結果 PASS/FAIL、commit hash（若有）以及是否推送成功。',
        ]);
    }

    /**
     * @return array<ToolInterface>
     */
    protected function tools(): array
    {
        return [
            $this->readFileTool(),
            $this->writeFileTool(),
            $this->runPintTool(),
            $this->runTestsTool(),
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

        return str_starts_with($path, '.ai-dev/')
            || str_starts_with($path, '.cursor/rules/')
            || in_array($path, ['README.md', 'AGENTS.md', '.env.example'], true);
    }

    private function allowedReadPath(string $relativePath): bool
    {
        $path = ltrim($relativePath, '/');

        if (str_contains($path, '..')) {
            return false;
        }

        return str_starts_with($path, '.ai-dev/')
            || str_starts_with($path, '.cursor/rules/')
            || in_array($path, ['README.md', 'AGENTS.md', '.env.example', 'composer.json', 'composer.lock'], true);
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

    private function readFileTool(): ToolInterface
    {
        return Tool::make(
            name: 'read_file',
            description: '讀取專案檔案內容（只允許白名單路徑；路徑以 repo 根為相對路徑）。',
            properties: [
                ToolProperty::make('path', PropertyType::STRING, '相對於 repo 根的檔案路徑（例如 .ai-dev/development/README.md）。', true),
            ],
        )->setCallable(function (string $path): string {
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
            description: '在允許的白名單路徑寫入檔案內容（路徑以 repo 根為相對路徑）。',
            properties: [
                ToolProperty::make('path', PropertyType::STRING, '相對於 repo 根的檔案路徑（例如 .ai-dev/development/README.md）。', true),
                ToolProperty::make('content', PropertyType::STRING, '要寫入的檔案內容（UTF-8）。', true),
            ],
        )->setCallable(function (string $path, string $content): string {
            if (! $this->allowedWritePath($path)) {
                return 'ERROR: path is not allowed for writing.';
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
        )->setCallable(function (): string {
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
        )->setCallable(function (): string {
            $result = $this->runProcess('php artisan test --compact', 900);

            return json_encode([
                'exitCode' => $result['exitCode'],
                'success' => $result['success'],
                'stdoutTail' => mb_substr($result['stdout'], -4000),
                'stderrTail' => mb_substr($result['stderr'], -4000),
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    private function gitCommitPushTool(): ToolInterface
    {
        return Tool::make(
            name: 'git_commit_push',
            description: '若目前白名單範圍內有變更：git add、commit、並 push 到 origin（推送到目前分支）。若無變更則回傳 NO_CHANGES。',
            properties: [
                ToolProperty::make('message', PropertyType::STRING, 'commit message', true),
            ],
        )->setCallable(function (string $message): string {
            // Always stage only known safe areas to avoid accidental commits.
            $stageCmd = 'git add .ai-dev .cursor/rules README.md AGENTS.md .env.example';
            $this->runProcess($stageCmd, 120);

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
