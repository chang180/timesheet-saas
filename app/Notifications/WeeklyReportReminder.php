<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Company $company,
        public int $workYear,
        public int $workWeek
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
        $url = route('tenant.weekly-reports.create', $this->company);

        return (new MailMessage)
            ->subject("[{$this->company->name}] 週報提醒 - 第 {$this->workWeek} 週")
            ->markdown('mail.weekly-report.reminder', [
                'company' => $this->company,
                'user' => $notifiable,
                'workYear' => $this->workYear,
                'workWeek' => $this->workWeek,
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
            'work_year' => $this->workYear,
            'work_week' => $this->workWeek,
        ];
    }
}
