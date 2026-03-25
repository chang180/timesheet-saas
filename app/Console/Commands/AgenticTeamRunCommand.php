<?php

namespace App\Console\Commands;

use App\Neuron\Agents\ToolExecutorAgent;
use App\Neuron\Providers\MinimaxAnthropic;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\UserMessage;

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
        $runNeuron = (bool) $this->option('neuron');
        $offline = (bool) $this->option('offline');

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
        $outPath = $outDir.'/orchestrator-entry-'.$this->slugify($epicId).'-'.$now->format('Ymd-His').'.md';
        File::put($outPath, $generatedMarkdown);

        $this->line('');
        $this->info('Saved: '.$outPath);

        if ($runNeuron) {
            $runToolExec = (bool) $this->option('toolexec');

            if ($runToolExec) {
                $this->line('');
                $this->info('=== ToolExecutorAgent：套用變更→測試→commit/push ===');

                $toolExecOutput = $this->runToolExecutorAgent(
                    epicId: $epicId,
                    offline: $offline
                );

                $neuronPath = $outDir.'/orchestrator-toolexec-output-'.$this->slugify($epicId).'-'.$now->format('Ymd-His').'.md';
                File::put($neuronPath, $toolExecOutput);

                $this->line('');
                $this->line($toolExecOutput);
                $this->info('Saved: '.$neuronPath);
            } else {
                $this->line('');
                $this->info('=== Neuron runtime：最小決策報告 ===');

                $neuronOutput = $this->runNeuronAgent(
                    epicId: $epicId,
                    offline: $offline
                );

                $neuronPath = $outDir.'/orchestrator-neuron-output-'.$this->slugify($epicId).'-'.$now->format('Ymd-His').'.md';
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
        if ($offline) {
            return implode("\n", [
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
            maxTokens: 600,
            parameters: [
                'temperature' => 0.2,
            ],
        );

        $agent->setAiProvider($provider);

        // Keep prompt intentionally small for quick results.
        $agent->setInstructions(implode("\n", [
            '你是一個在 agentic 開發流程中協助 Orchestrator 的助理。',
            '給定 epicId，請產出一份「短版 Markdown 待決策報告 stub」。',
            '必須使用以下章節（用 Markdown headings）：',
            '- 摘要',
            '- 風險',
            '- 需要人類決策的問題',
            '- 建議下一個史詩 ID',
            '內容保持精簡，建議低於 ~250 行。',
        ]));

        $userPrompt = implode("\n", [
            '史詩 ID： '.$epicId,
            '',
            '請立刻產出該 stub（僅輸出 Markdown 內容）。',
        ]);

        $response = $agent
            ->chat(new UserMessage($userPrompt))
            ->getMessage()
            ->getContent();

        return ($response ?? '')."\n";
    }

    private function runToolExecutorAgent(string $epicId, bool $offline): string
    {
        if ($offline) {
            return implode("\n", [
                '# ToolExecutorAgent 執行輸出（離線 stub）',
                '',
                '史詩 ID：'.$epicId,
                '注意：已啟用 `--offline`，不會呼叫 Neuron LLM，也不會套用任何變更。',
            ])."\n";
        }

        $agent = ToolExecutorAgent::make();

        $userPrompt = implode("\n", [
            '史詩 ID：'.$epicId,
            '',
            '目標：',
            '- 在白名單允許的範圍內，修正文件/規格不一致（若有）。',
            '- 修正後執行 pint（若適用）與 `php artisan test --compact`。',
            '- 測試 PASS 後 commit/push；FAIL 則嘗試修正後重試。',
            '',
            '重要：你只能透過工具 read_file/write_file/run_pint/run_tests/git_commit_push 完成操作。',
        ]);

        return $agent
            ->chat(new UserMessage($userPrompt))
            ->getMessage()
            ->getContent()."\n";
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
