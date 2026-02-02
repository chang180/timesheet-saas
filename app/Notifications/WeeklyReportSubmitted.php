<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Company $company,
        public WeeklyReport $weeklyReport,
        public User $submittedBy
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('tenant.weekly-reports.preview', [$this->company, $this->weeklyReport]);

        return (new MailMessage)
            ->subject("[{$this->company->name}] {$this->submittedBy->name} 已提交週報")
            ->markdown('mail.weekly-report.submitted', [
                'company' => $this->company,
                'weeklyReport' => $this->weeklyReport,
                'submittedBy' => $this->submittedBy,
                'manager' => $notifiable,
                'url' => $url,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'weekly_report_id' => $this->weeklyReport->id,
            'submitted_by' => $this->submittedBy->id,
        ];
    }
}
