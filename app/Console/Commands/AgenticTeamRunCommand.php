<?php

namespace App\Console\Commands;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AgenticTeamRunCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'agentic-team:run {epicId? : Epic ID (e.g. EPIC-01-doc-alignment)}';

    /**
     * @var string
     */
    protected $description = 'Generate an Orchestrator entry message + task packages for Agentic Team';

    public function handle(): int
    {
        $epicId = (string) $this->argument('epicId') ?: 'EPIC-01-doc-alignment';
        $now = new DateTimeImmutable('now');

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

        return self::SUCCESS;
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
