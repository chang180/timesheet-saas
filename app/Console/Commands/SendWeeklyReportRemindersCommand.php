<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Notifications\WeeklyReportReminder;
use Illuminate\Console\Command;

class SendWeeklyReportRemindersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'weekly-report:send-reminders';

    /**
     * @var string
     */
    protected $description = 'Send weekly report reminders to users who have not submitted their reports';

    public function handle(): int
    {
        $workYear = (int) now()->isoWeekYear();
        $workWeek = (int) now()->isoWeek();

        $this->info("Sending reminders for week {$workWeek} of {$workYear}...");

        $companies = Company::query()
            ->whereNotNull('onboarded_at')
            ->with(['settings'])
            ->get();

        $totalReminders = 0;

        foreach ($companies as $company) {
            if (! $this->shouldSendReminder($company)) {
                continue;
            }

            $usersWithoutReport = User::query()
                ->where('company_id', $company->id)
                ->whereDoesntHave('weeklyReports', function ($query) use ($workYear, $workWeek) {
                    $query->where('work_year', $workYear)
                        ->where('work_week', $workWeek)
                        ->where('status', WeeklyReport::STATUS_SUBMITTED);
                })
                ->get();

            foreach ($usersWithoutReport as $user) {
                $user->notify(new WeeklyReportReminder($company, $workYear, $workWeek));
                $totalReminders++;
            }
        }

        $this->info("Sent {$totalReminders} reminders.");

        return self::SUCCESS;
    }

    private function shouldSendReminder(Company $company): bool
    {
        $preferences = $company->settings?->notification_preferences ?? [];

        return $preferences['weekly_reminder_enabled'] ?? true;
    }
}
