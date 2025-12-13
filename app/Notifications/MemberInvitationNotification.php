<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberInvitationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Company $company,
        public string $invitationToken,
        public string $inviterName
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('tenant.invitations.accept', [
            'company' => $this->company->slug,
            'token' => $this->invitationToken,
        ]);

        return (new MailMessage)
            ->subject("邀請加入 {$this->company->name}")
            ->greeting("您好，{$notifiable->name}！")
            ->line("{$this->inviterName} 邀請您加入 {$this->company->name} 的週報系統。")
            ->line('請點擊下方按鈕設定您的密碼並開始使用系統。')
            ->action('接受邀請', $acceptUrl)
            ->line('此邀請連結將在 7 天後過期。')
            ->line('如果您沒有要求此邀請，請忽略此郵件。');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'inviter_name' => $this->inviterName,
        ];
    }
}
