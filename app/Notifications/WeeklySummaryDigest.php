<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklySummaryDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public Company $company,
        public int $workYear,
        public int $workWeek,
        public array $summary
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
        $url = route('tenant.weekly-reports.summary', [
            'company' => $this->company,
            'year' => $this->workYear,
            'week' => $this->workWeek,
        ]);

        return (new MailMessage)
            ->subject("[{$this->company->name}] 週報匯總 - 第 {$this->workWeek} 週")
            ->markdown('mail.weekly-report.digest', [
                'company' => $this->company,
                'manager' => $notifiable,
                'workYear' => $this->workYear,
                'workWeek' => $this->workWeek,
                'summary' => $this->summary,
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
            'summary' => $this->summary,
        ];
    }
}
