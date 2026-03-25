<?php

namespace App\Console\Commands;

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
    protected $signature = 'agentic-team:run {epicId? : Epic ID (e.g. EPIC-01-doc-alignment)} {--neuron : Run a minimal Neuron agent to generate a short decision report (use Inspector for timeline)} {--offline : When used with --neuron, skip the real LLM call and generate a deterministic stub for testing}';

    /**
     * @var string
     */
    protected $description = 'Generate an Orchestrator entry message + task packages for Agentic Team';

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
            $this->error('Missing orchestrator role file: '.$orchestratorRolePath);

            return self::FAILURE;
        }

        if (! File::exists($decisionReportTemplatePath) || ! File::exists($taskPackageTemplatePath)) {
            $this->error('Missing one or more agentic-team templates under .ai-dev/agentic-team/templates/');

            return self::FAILURE;
        }

        $taskRoles = ['SecurityAgent', 'FeatureCompletenessAgent', 'TestCoverageAgent', 'UXConsistencyAgent'];

        $this->line('');
        $this->info('=== Agentic Team: Orchestrator entry generated ===');
        $this->line('');
        $this->warn('Note: This command only generates the structured “Orchestrator” message. Neuron runtime is intentionally deferred in this repo.');
        $this->line('');

        $orchestratorRole = File::get($orchestratorRolePath);
        $taskPackageTemplate = File::get($taskPackageTemplatePath);
        $decisionReportTemplate = File::get($decisionReportTemplatePath);

        $content = [];
        $content[] = '# Orchestrator Mode: Starting';
        $content[] = '';
        $content[] = 'Epic ID: '.$epicId;
        $content[] = 'Run date (UTC or server time): '.$now->format('Y-m-d H:i:s');
        $content[] = '';
        $content[] = 'You are the **Orchestrator** and your job is to:';
        $content[] = '- decompose the epic into task packages';
        $content[] = '- dispatch to four specialists';
        $content[] = '- merge outputs and produce a decision-ready report';
        $content[] = '';
        $content[] = '### Required output';
        $content[] = '- One task package per specialist role (use the structure from task-package.md)';
        $content[] = '- One merged decision report (use decision-report.md structure)';
        $content[] = '';
        $content[] = '### Orchestrator role reference (do not paste fully unless needed)';
        $content[] = '```';
        $content[] = $this->truncateForConsole($orchestratorRole, 3500);
        $content[] = '```';
        $content[] = '';
        $content[] = '---';
        $content[] = '';
        $content[] = '# Task packages (dispatch)';
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

        $content[] = '# Decision report (merged)';
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
            $this->line('');
            $this->info('=== Neuron runtime: minimal decision report ===');

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
                '# Neuron Runtime Output (OFFLINE STUB)',
                '',
                'Epic ID: '.$epicId,
                'Timestamp: '.(new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                '',
                'Note: `--offline` was set, so Neuron LLM call was skipped. This is only for local testing stability.',
            ])."\n";
        }

        $key = (string) config('neuron.anthropic.key');
        $baseUri = (string) config('neuron.anthropic.base_uri');
        $model = (string) config('neuron.anthropic.model');

        if ($key === '' || $baseUri === '' || $model === '') {
            $this->error('Missing Neuron provider configuration (neuron.anthropic.*). Please set ANTHROPIC_BASE_URL/ANTHROPIC_API_KEY/ANTHROPIC_MODEL in .env.');

            return implode("\n", [
                '# Neuron Runtime Output (ERROR)',
                '',
                'Epic ID: '.$epicId,
                'Timestamp: '.(new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                '',
                'Reason: Missing Neuron provider configuration.',
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
            'You are an Orchestrator assistant inside an agentic development workflow.',
            'Given an epicId, output a short markdown decision report stub.',
            'Required sections (use headings):',
            '- Summary',
            '- Risks',
            '- Questions for human',
            '- Suggested next epic id',
            'Keep under ~250 lines.',
        ]));

        $userPrompt = implode("\n", [
            'Epic ID: '.$epicId,
            '',
            'Generate the decision report stub now.',
        ]);

        $response = $agent
            ->chat(new UserMessage($userPrompt))
            ->getMessage()
            ->getContent();

        return ($response ?? '')."\n";
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
