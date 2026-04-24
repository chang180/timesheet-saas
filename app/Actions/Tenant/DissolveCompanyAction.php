<?php

namespace App\Actions\Tenant;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\DB;

class DissolveCompanyAction
{
    /**
     * @return array{company_id: int, moved_personal_reports: int}
     */
    public function execute(Company $company, User $sole): array
    {
        $userCount = User::query()
            ->where('company_id', $company->id)
            ->count();

        abort_unless($userCount === 1, 422, '公司仍有其他成員，無法清空。');
        abort_unless($sole->company_id === $company->id, 422, '此用戶不屬於該公司。');

        return DB::transaction(function () use ($company, $sole): array {
            $tenant = Company::query()
                ->whereKey($company->id)
                ->lockForUpdate()
                ->firstOrFail();

            $movedReports = WeeklyReport::query()
                ->where('company_id', $tenant->id)
                ->where('user_id', $sole->id)
                ->update([
                    'company_id' => null,
                    'division_id' => null,
                    'department_id' => null,
                    'team_id' => null,
                ]);

            WeeklyReport::query()->where('company_id', $tenant->id)->delete();

            Team::query()->where('company_id', $tenant->id)->delete();
            Department::query()->where('company_id', $tenant->id)->delete();
            Division::query()->where('company_id', $tenant->id)->delete();

            CompanySetting::query()->where('company_id', $tenant->id)->delete();

            AuditLog::create([
                'company_id' => $tenant->id,
                'user_id' => $sole->id,
                'event' => 'company.dissolved',
                'description' => "{$sole->name} 關閉了公司「{$tenant->name}」",
                'auditable_type' => Company::class,
                'auditable_id' => $tenant->id,
                'properties' => [
                    'reason' => 'sole_user_left',
                    'moved_personal_reports' => $movedReports,
                ],
                'occurred_at' => now(),
            ]);

            $sole->forceFill([
                'company_id' => null,
                'division_id' => null,
                'department_id' => null,
                'team_id' => null,
                'role' => 'member',
                'registered_via' => 'personal-after-dissolve',
            ])->save();

            $tenant->forceFill([
                'status' => 'dissolved',
                'current_user_count' => 0,
                'suspended_at' => now(),
            ])->save();
            $tenant->delete();

            return [
                'company_id' => $tenant->id,
                'moved_personal_reports' => $movedReports,
            ];
        });
    }
}
