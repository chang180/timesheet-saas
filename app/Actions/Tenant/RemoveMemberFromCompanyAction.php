<?php

namespace App\Actions\Tenant;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveMemberFromCompanyAction
{
    public function execute(Company $company, User $member, User $actor): void
    {
        if (! $actor->isCompanyAdmin() || $actor->company_id !== $company->id) {
            abort(403, '僅公司管理者可移出成員。');
        }

        if ($member->company_id !== $company->id) {
            abort(422, '此成員不屬於該公司。');
        }

        if ($member->id === $actor->id) {
            throw ValidationException::withMessages([
                'member' => ['無法在此移出自己；若你是公司唯一使用者，請使用「關閉公司」。'],
            ]);
        }

        if ($member->isCompanyAdmin()) {
            $remainingAdmins = User::query()
                ->where('company_id', $company->id)
                ->where('role', 'company_admin')
                ->where('id', '!=', $member->id)
                ->count();

            if ($remainingAdmins === 0) {
                throw ValidationException::withMessages([
                    'member' => ['至少需保留一位公司管理者，請先指派其他管理者。'],
                ]);
            }
        }

        $previousRole = $member->role;

        $member->leaveCurrentCompany();

        AuditLog::create([
            'company_id' => $company->id,
            'user_id' => $actor->id,
            'event' => 'member.removed',
            'description' => "{$actor->name} 移出成員 {$member->name}",
            'auditable_type' => User::class,
            'auditable_id' => $member->id,
            'properties' => [
                'member_email' => $member->email,
                'previous_role' => $previousRole,
            ],
            'occurred_at' => now(),
        ]);
    }
}
