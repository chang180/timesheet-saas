<?php

namespace App\Actions\Tenant;

use App\Models\AuditLog;
use App\Models\User;

class InvalidateOtherPendingInvitationsAction
{
    /**
     * Invalidate all pending email-based invitations for the given email
     * except for the user with $keepUserId (who just joined a company).
     *
     * Note: link-based org invitations (divisions/departments/teams) are NOT
     * invalidated here — they're blocked at the middleware level for users
     * who already have a company_id.
     */
    public function execute(string $email, int $keepUserId): int
    {
        $pendingInvites = User::query()
            ->where('email', $email)
            ->where('id', '!=', $keepUserId)
            ->whereNotNull('invitation_token')
            ->whereNull('invitation_accepted_at')
            ->get();

        foreach ($pendingInvites as $invite) {
            $invite->forceFill([
                'invitation_token' => null,
                'invitation_revoked_at' => now(),
            ])->save();

            AuditLog::create([
                'company_id' => $invite->company_id,
                'user_id' => $keepUserId,
                'event' => 'invitation.invalidated_by_join',
                'description' => "用戶 {$email} 加入其他公司後，此邀請自動失效。",
                'auditable_type' => User::class,
                'auditable_id' => $invite->id,
                'properties' => ['email' => $email],
                'occurred_at' => now(),
            ]);
        }

        return $pendingInvites->count();
    }
}
