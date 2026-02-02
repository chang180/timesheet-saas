<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Notifications\WeeklySummaryDigest;
use Illuminate\Console\Command;

class SendWeeklySummaryDigestCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'weekly-report:send-digest {--week= : The week number to send digest for}';

    /**
     * @var string
     */
    protected $description = 'Send weekly summary digest to managers';

    public function handle(): int
    {
        $workYear = (int) now()->isoWeekYear();
        $workWeek = (int) ($this->option('week') ?? now()->subWeek()->isoWeek());

        $this->info("Sending digests for week {$workWeek} of {$workYear}...");

        $companies = Company::query()
            ->whereNotNull('onboarded_at')
            ->with(['settings'])
            ->get();

        $totalDigests = 0;

        foreach ($companies as $company) {
            if (! $this->shouldSendDigest($company)) {
                continue;
            }

            $summary = $this->buildSummary($company, $workYear, $workWeek);

            if ($summary['report_count'] === 0) {
                continue;
            }

            $managers = User::query()
                ->where('company_id', $company->id)
                ->whereIn('role', ['company_admin', 'division_lead', 'department_manager'])
                ->get();

            foreach ($managers as $manager) {
                $manager->notify(new WeeklySummaryDigest($company, $workYear, $workWeek, $summary));
                $totalDigests++;
            }
        }

        $this->info("Sent {$totalDigests} digests.");

        return self::SUCCESS;
    }

    private function shouldSendDigest(Company $company): bool
    {
        $preferences = $company->settings?->notification_preferences ?? [];

        return $preferences['summary_digest_enabled'] ?? true;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(Company $company, int $workYear, int $workWeek): array
    {
        $reports = WeeklyReport::query()
            ->where('company_id', $company->id)
            ->where('work_year', $workYear)
            ->where('work_week', $workWeek)
            ->with(['items'])
            ->get();

        $totalHours = 0;
        $billableHours = 0;
        $submittedCount = 0;
        $draftCount = 0;
        $memberIds = [];

        foreach ($reports as $report) {
            if ($report->status === WeeklyReport::STATUS_SUBMITTED) {
                $submittedCount++;
            } else {
                $draftCount++;
            }

            $memberIds[$report->user_id] = true;

            foreach ($report->items as $item) {
                if ($item->type === WeeklyReportItem::TYPE_CURRENT_WEEK) {
                    $totalHours += $item->hours_spent ?? 0;
                    if ($item->is_billable) {
                        $billableHours += $item->hours_spent ?? 0;
                    }
                }
            }
        }

        return [
            'total_hours' => round($totalHours, 2),
            'billable_hours' => round($billableHours, 2),
            'report_count' => $reports->count(),
            'submitted_count' => $submittedCount,
            'draft_count' => $draftCount,
            'member_count' => count($memberIds),
        ];
    }
}
