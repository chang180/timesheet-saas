<?php

namespace App\Console\Commands;

use App\Neuron\Agents\ToolExecutorAgent;
use App\Neuron\Providers\MinimaxAnthropic;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Process\Process;

class AgenticTeamRunCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'agentic-team:run {epicId? : 史詩 ID（例如 EPIC-01-doc-alignment）} {--neuron : 執行最小 Neuron Agent 以產出短版決策報告（用 Inspector 追蹤時間軸）} {--toolexec : 在 --neuron 下改由 ToolExecutorAgent 嘗試套用變更→跑測試→pass 才 commit/push} {--offline : 搭配 --neuron（或 --toolexec）時跳過真實 LLM 呼叫，改用可預期 stub 供測試}';

    /**
     * @var string
     */
    protected $description = '為 Agentic Team 產出 Orchestrator 入場訊息 + 任務包（可選 Neuron/ToolExecutor）';

    public function handle(): int
    {
        $epicId = (string) $this->argument('epicId') ?: 'EPIC-01-doc-alignment';
        $now = new DateTimeImmutable('now');
        $runId = $now->format('Ymd-His').'-pid'.getmypid();
        $runNeuron = (bool) $this->option('neuron');
        $offlineOption = $this->option('offline');
        $offline = (bool) $offlineOption;

        $orchestratorRolePath = base_path('.ai-dev/agentic-team/roles/orchestrator.md');
        $decisionReportTemplatePath = base_path('.ai-dev/agentic-team/templates/decision-report.md');
        $taskPackageTemplatePath = base_path('.ai-dev/agentic-team/templates/task-package.md');

        if (! File::exists($orchestratorRolePath)) {
            $this->error('找不到 Orchestrator 角色檔：'.$orchestratorRolePath);

            return self::FAILURE;
        }

        if (! File::exists($decisionReportTemplatePath) || ! File::exists($taskPackageTemplatePath)) {
            $this->error('找不到 agentic-team 範本（必需檔案位於 .ai-dev/agentic-team/templates/）');

            return self::FAILURE;
        }

        $taskRoles = [
            'SecurityAgent',
            'FeatureCompletenessAgent',
            'TestCoverageAgent',
            'UXConsistencyAgent',
            'ToolExecutorAgent',
        ];

        $this->line('');
        $this->info('=== Agentic Team：Orchestrator 入場訊息已生成 ===');
        $this->line('');
        $this->warn('注意：此指令僅產出結構化「Orchestrator」訊息；Neuron runtime 在此 repo 內刻意延後，除非加上 --neuron。');
        $this->line('');

        $orchestratorRole = File::get($orchestratorRolePath);
        $taskPackageTemplate = File::get($taskPackageTemplatePath);
        $decisionReportTemplate = File::get($decisionReportTemplatePath);

        $content = [];
        $content[] = '# Orchestrator 模式：開始';
        $content[] = '';
        $content[] = '史詩 ID： '.$epicId;
        $content[] = '執行時間（伺服器時間）：'.$now->format('Y-m-d H:i:s');
        $content[] = 'Run ID：'.$runId;
        $content[] = '';
        $content[] = '你是 **Orchestrator**，你的工作是：';
        $content[] = '- 將史詩拆成四份任務包';
        $content[] = '- 指派給四位專責角色';
        $content[] = '- 合併回覆並產出「待決策報告」';
        $content[] = '';
        $content[] = '### 必要輸出';
        $content[] = '- 每位專責角色各一份任務包（使用 task-package.md 結構）';
        $content[] = '- 合併後一份待決策報告（使用 decision-report.md 結構）';
        $content[] = '';
        $content[] = '### Orchestrator 角色參考（除非需要，勿貼全文）';
        $content[] = '```';
        $content[] = $this->truncateForConsole($orchestratorRole, 3500);
        $content[] = '```';
        $content[] = '';
        $content[] = '---';
        $content[] = '';
        $content[] = '# 任務包（派工）';
        $content[] = '';

        foreach ($taskRoles as $role) {
            $package = $taskPackageTemplate;

            // Use regex to tolerate whitespace differences in templates.
            $package = (string) preg_replace(
                '/\*\*史詩 ID\*\*：\s*/u',
                '**史詩 ID**： '.$epicId.'  ',
                $package,
                1
            );

            $package = (string) preg_replace(
                '/\*\*派工日期\*\*：\s*/u',
                '**派工日期**： '.$now->format('Y-m-d').'  ',
                $package,
                1
            );

            $package = (string) preg_replace(
                '/\*\*受命角色\*\*：\s*（[^）]*）/u',
                '**受命角色**：'.$role,
                $package,
                1
            );

            $content[] = '## '.$role;
            $content[] = '';
            $content[] = $this->truncateForConsole($package, 2400);
            $content[] = '';
            $content[] = '---';
            $content[] = '';
        }

        $content[] = '# 待決策報告（合併）';
        $content[] = '';
        $content[] = $decisionReportTemplate;

        $generatedMarkdown = implode("\n", $content)."\n";

        $this->line($generatedMarkdown);

        $outDir = storage_path('app/agentic-team');
        File::ensureDirectoryExists($outDir);
        $outPath = $outDir.'/orchestrator-entry-'.$this->slugify($epicId).'-'.$runId.'.md';
        File::put($outPath, $generatedMarkdown);

        $this->line('');
        $this->info('Saved: '.$outPath);

        if ($runNeuron) {
            $runToolExec = (bool) $this->option('toolexec');

            if ($runToolExec) {
                $this->line('');
                $this->info('=== ToolExecutorAgent：套用變更→測試→commit/push ===');
                $this->line('（Debug）--offline(bool): '.($offline ? 'ON' : 'OFF'));
                $this->line('（Debug）--offline(raw): '.var_export($offlineOption, true));

                $toolExecOutput = $this->runToolExecutorAgent(
                    epicId: $epicId,
                    offline: $offline
                );

                $neuronPath = $outDir.'/orchestrator-toolexec-output-'.$this->slugify($epicId).'-'.$runId.'.md';
                File::put($neuronPath, $toolExecOutput);

                $this->line('');
                $this->line($toolExecOutput);
                $this->info('Saved: '.$neuronPath);
            } else {
                $this->line('');
                $this->info('=== Neuron runtime：最小決策報告 ===');
                $this->line('（Debug）--offline(bool): '.($offline ? 'ON' : 'OFF'));
                $this->line('（Debug）--offline(raw): '.var_export($offlineOption, true));

                $neuronOutput = $this->runNeuronAgent(
                    epicId: $epicId,
                    offline: $offline
                );

                $neuronPath = $outDir.'/orchestrator-neuron-output-'.$this->slugify($epicId).'-'.$runId.'.md';
                File::put($neuronPath, $neuronOutput);

                $this->line('');
                $this->line($neuronOutput);
                $this->info('Saved: '.$neuronPath);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Run a minimal Neuron Agent so Inspector can show a real execution timeline.
     *
     * @param  bool  $offline  When true, skip external LLM call and return deterministic stub.
     */
    private function runNeuronAgent(string $epicId, bool $offline): string
    {
        $optionOfflineRaw = $this->option('offline');
        $optionOfflineBool = (bool) $optionOfflineRaw;

        $debugHeader = implode("\n", [
            '# NeuronAgent（Debug Header）',
            '',
            'passedOffline(bool)：'.($offline ? 'ON' : 'OFF'),
            'optionOffline(bool)：'.($optionOfflineBool ? 'ON' : 'OFF'),
            'optionOffline(raw)：'.var_export($optionOfflineRaw, true),
            '',
        ]);

        if ($offline) {
            return implode("\n", [
                $debugHeader,
                '# Neuron 執行輸出（離線 stub）',
                '',
                '史詩 ID： '.$epicId,
                '時間戳： '.(new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                '',
                '注意：已啟用 `--offline`，因此 Neuron LLM 呼叫被跳過；此僅用於本機測試穩定性。',
            ])."\n";
        }

        $key = (string) config('neuron.anthropic.key');
        $baseUri = (string) config('neuron.anthropic.base_uri');
        $model = (string) config('neuron.anthropic.model');

        if ($key === '' || $baseUri === '' || $model === '') {
            $this->error('缺少 Neuron 供應商設定（neuron.anthropic.*）。請在 .env 設定 ANTHROPIC_BASE_URL/ANTHROPIC_API_KEY/ANTHROPIC_MODEL。');

            return implode("\n", [
                '# Neuron 執行輸出（錯誤）',
                '',
                '史詩 ID： '.$epicId,
                '時間戳： '.(new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                '',
                '原因：Neuron 供應商設定缺失。',
            ])."\n";
        }

        $agent = Agent::make();

        $provider = new MinimaxAnthropic(
            key: $key,
            model: $model,
            baseUriOverride: $baseUri,
            maxTokens: 220,
            parameters: [
                'temperature' => 0.2,
            ],
        );

        $agent->setAiProvider($provider);

        // Keep prompt intentionally small for quick results.
        $agent->setInstructions(implode("\n", [
            '語言規定：所有輸出一律使用繁體中文。禁止輸出英文或任何推理過程，直接輸出結果。',
            '',
            '你是一個在 agentic 開發流程中協助 Orchestrator 的助理。',
            '給定 epicId，請產出一份「短版 Markdown 待決策報告 stub」。',
            '必須使用以下章節（用 Markdown headings）：',
            '- 摘要',
            '- 風險與嚴重度',
            '- 需要人類決策的問題',
            '- 建議下一史詩 ID',
            '內容保持精簡，低於 ~120 行；只輸出 Markdown 本文，不要任何前言或後記。',
        ]));

        $userPrompt = implode("\n", [
            '史詩 ID： '.$epicId,
            '',
            '請立刻產出該 stub（僅輸出 Markdown 內容）。',
        ]);

        $maxAttempts = 3;
        $lastErrorMessage = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $agent
                    ->chat(new UserMessage($userPrompt))
                    ->getMessage()
                    ->getContent();

                return $debugHeader.(($response ?? '') !== '' ? "\n".$response : '')."\n";
            } catch (\NeuronAI\Exceptions\HttpException $e) {
                $lastErrorMessage = $e->getMessage();

                // Simple backoff to reduce immediate retries during transient network issues.
                if ($attempt < $maxAttempts) {
                    usleep((int) (500_000 * $attempt)); // 0.5s, 1.0s ...

                    continue;
                }
            } catch (\Throwable $e) {
                // Catch-all: avoid throwing to Inspector when upstream network flaps.
                $lastErrorMessage = $e->getMessage();
                break;
            }
        }

        return implode("\n", [
            $debugHeader,
            '# Neuron 執行輸出（錯誤）',
            '',
            '史詩 ID： '.$epicId,
            '原因：'.$lastErrorMessage,
            '',
            '備註：本次已嘗試重試（'.$maxAttempts.' 次）仍失敗；請稍後再跑一次或改用 `--offline`。',
        ])."\n";
    }

    private function runToolExecutorAgent(string $epicId, bool $offline): string
    {
        $optionOfflineRaw = $this->option('offline');
        $optionOfflineBool = (bool) $optionOfflineRaw;

        $debugHeader = implode("\n", [
            '# ToolExecutorAgent（Debug Header）',
            '',
            'passedOffline(bool)：'.($offline ? 'ON' : 'OFF'),
            'optionOffline(bool)：'.($optionOfflineBool ? 'ON' : 'OFF'),
            'optionOffline(raw)：'.var_export($optionOfflineRaw, true),
            '',
        ]);

        if ($offline) {
            return $debugHeader.implode("\n", [
                '# ToolExecutorAgent 執行輸出（離線 stub）',
                '',
                '史詩 ID：'.$epicId,
                '注意：已啟用 `--offline`，不會呼叫 Neuron LLM，也不會套用任何變更。',
            ])."\n";
        }

        // Run tests in this process first so the agent starts with full context
        // instead of wasting tool budget on blind exploration.
        $this->line('（先在本機跑一次測試，結果會附在 agent 的初始訊息內）');
        $preTestProcess = Process::fromShellCommandline('php artisan test --compact', base_path());
        $preTestProcess->setTimeout(900);
        $preTestProcess->run();
        $preTestPassed = $preTestProcess->isSuccessful();
        $preTestOutput = mb_substr((string) $preTestProcess->getOutput(), -4000)
            .(($preTestProcess->getErrorOutput() !== '') ? "\nSTDERR:\n".mb_substr((string) $preTestProcess->getErrorOutput(), -2000) : '');

        $agent = ToolExecutorAgent::make();
        $agent->toolMaxRuns(30);

        if ($preTestPassed) {
            $userPrompt = implode("\n", [
                '史詩 ID：'.$epicId,
                '',
                '【已知狀態】測試全部通過（以下為測試輸出）：',
                '```',
                $preTestOutput,
                '```',
                '',
                '任務：若目前 repo 有未提交的變更（git diff --cached 或 working tree），呼叫 `git_commit_push` 提交。若無任何變更，直接輸出「所有測試通過，無需提交」。',
                '不需要呼叫 run_tests（已知通過）、find_files、read_file 或 write_file。',
            ]);
        } else {
            $userPrompt = implode("\n", [
                '史詩 ID：'.$epicId,
                '',
                '【已知狀態】測試失敗（以下為測試輸出）：',
                '```',
                $preTestOutput,
                '```',
                '',
                '任務：根據上方失敗訊息，用 `find_files` 定位相關檔案（每次只搜尋一個具體目錄），再用 `read_file` 讀取，用 `write_file` 修正，跑 `run_pint`（若有 PHP 變更），再跑 `run_tests` 驗證。PASS 後呼叫 `git_commit_push`。',
                '限制：只搜尋與失敗測試直接相關的目錄；不要讀取與修復無關的檔案。',
            ]);
        }

        $maxAttempts = 2;
        $lastError = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $content = $agent
                    ->chat(new UserMessage($userPrompt))
                    ->getMessage()
                    ->getContent();

                return $debugHeader.($content ?? '')."\n";
            } catch (\NeuronAI\Exceptions\ToolRunsExceededException $e) {
                // No point retrying — tool limit is deterministic for this session.
                return implode("\n", [
                    $debugHeader,
                    '# ToolExecutorAgent 執行輸出（錯誤：工具呼叫次數超限）',
                    '',
                    '史詩 ID：'.$epicId,
                    '原因：'.$e->getMessage(),
                    '',
                    '備註：請縮小任務範圍或分批執行。',
                ])."\n";
            } catch (\NeuronAI\Exceptions\HttpException $e) {
                $lastError = $e->getMessage();

                if ($attempt < $maxAttempts) {
                    usleep((int) (1_000_000 * $attempt)); // 1s, 2s...

                    continue;
                }
            } catch (\Throwable $e) {
                $lastError = get_class($e).'：'.$e->getMessage();
                break;
            }
        }

        // All retries exhausted — determine which error bucket to use.
        $isHttp = str_contains($lastError, 'Network error') || str_contains($lastError, 'cURL') || str_contains($lastError, '520') || str_contains($lastError, '429');

        if ($isHttp) {
            return implode("\n", [
                $debugHeader,
                '# ToolExecutorAgent 執行輸出（錯誤：HTTP 通訊失敗）',
                '',
                '史詩 ID：'.$epicId,
                '原因（最後一次）：'.$lastError,
                '已重試：'.$maxAttempts.' 次',
                '',
                '備註：請確認 ANTHROPIC_BASE_URL/ANTHROPIC_API_KEY，或稍後再試。',
            ])."\n";
        }

        return implode("\n", [
            $debugHeader,
            '# ToolExecutorAgent 執行輸出（錯誤）',
            '',
            '史詩 ID：'.$epicId,
            '原因：'.$lastError,
            '',
            '備註：執行期間發生未預期錯誤；可加上 --offline 進行本機測試。',
        ])."\n";
    }

    /**
     * Keep console output manageable.
     */
    private function truncateForConsole(string $text, int $maxChars): string
    {
        $trimmed = trim($text);

        if (mb_strlen($trimmed) <= $maxChars) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $maxChars)."\n\n[...truncated...]";
    }

    private function slugify(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim((string) $value, '-');
    }
}
