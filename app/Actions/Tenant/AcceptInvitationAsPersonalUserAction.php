<?php

namespace App\Actions\Tenant;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitationAsPersonalUserAction
{
    public function __construct(
        private readonly InvalidateOtherPendingInvitationsAction $invalidateInvitations,
    ) {}

    public function execute(
        User $personalUser,
        Company $company,
        Division|Department|Team|null $org,
        string $role = 'member',
    ): void {
        abort_unless($personalUser->isPersonal(), 422, '只有個人身份的用戶才能接受邀請。');

        DB::transaction(function () use ($personalUser, $company, $org, $role): void {
            $tenant = Company::query()
                ->whereKey($company->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tenant->current_user_count >= $tenant->user_limit) {
                throw ValidationException::withMessages([
                    'company' => ['此公司的成員數已達上限，請聯絡公司管理者。'],
                ]);
            }

            $payload = ['company_id' => $tenant->id, 'role' => $role];

            if ($org instanceof Division) {
                $payload['division_id'] = $org->id;
            } elseif ($org instanceof Department) {
                $payload['department_id'] = $org->id;
                $payload['division_id'] = $org->division_id;
            } elseif ($org instanceof Team) {
                $payload['team_id'] = $org->id;
                $payload['department_id'] = $org->department_id;
                $payload['division_id'] = $org->division_id;
            }

            $personalUser->forceFill($payload)->save();
            $tenant->increment('current_user_count');

            $this->invalidateInvitations->execute(
                email: $personalUser->email,
                keepUserId: $personalUser->id,
            );
        });
    }
}
