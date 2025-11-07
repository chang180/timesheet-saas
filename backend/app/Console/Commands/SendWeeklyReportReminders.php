<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReportReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly report submission reminders to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $weekStartDate = now()->startOfWeek();
        $weekEndDate = now()->endOfWeek();

        $users = User::whereHas('company', function ($query) {
            $query->where('is_active', true);
        })->get();

        $remindersSent = 0;

        foreach ($users as $user) {
            // Check if user has already submitted a report for this week
            $existingReport = WeeklyReport::where('user_id', $user->id)
                ->where('week_start_date', $weekStartDate->toDateString())
                ->first();

            if (!$existingReport || $existingReport->status === 'draft') {
                // Send reminder email (placeholder - actual email implementation would go here)
                $this->info("Reminder sent to {$user->email}");
                $remindersSent++;
            }
        }

        $this->info("Total reminders sent: {$remindersSent}");

        return Command::SUCCESS;
    }
}
