<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InviteMemberRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use App\Notifications\MemberInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberInviteController extends Controller
{
    public function store(InviteMemberRequest $request, Company $company): JsonResponse
    {
        $email = $request->invitedEmail();

        $data = $request->validated();

        $division = isset($data['division_id']) ? Division::find($data['division_id']) : null;
        $department = isset($data['department_id']) ? Department::find($data['department_id']) : null;
        $team = isset($data['team_id']) ? Team::find($data['team_id']) : null;

        if ($team && $department && $team->department_id !== $department->id) {
            abort(422, '選取的小組不屬於指定的部門。');
        }

        if ($team && ! $department) {
            $department = $team->department;
            $data['department_id'] = $department?->id;
        }

        if ($department && $division && $department->division_id !== $division->id) {
            abort(422, '選取的部門不屬於指定的事業群。');
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        $this->assertHierarchyMatchesRole($request->invitedRole(), $division, $department, $team);

        // Verify that level managers can only invite to their level or below
        $user = $request->user();
        if (! $user->isCompanyAdmin()) {
            $this->assertLevelManagerCanInvite($user, $division, $department, $team);
        }

        $user = DB::transaction(function () use ($request, $company, $division, $department, $team, $email): User {
            $tenant = Company::query()
                ->whereKey($company->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (User::query()
                ->where('company_id', $tenant->getKey())
                ->where('email', $email)
                ->exists()) {
                abort(422, '此電子郵件地址已經被使用。');
            }

            if ($tenant->current_user_count >= $tenant->user_limit) {
                abort(422, '此用戶的成員數已達上限。');
            }

            $invitationToken = Str::random(60);

            $user = User::create([
                'company_id' => $tenant->getKey(),
                'division_id' => $division?->id,
                'department_id' => $department?->id,
                'team_id' => $team?->id,
                'name' => $request->invitedName(),
                'email' => $email,
                'role' => $request->invitedRole(),
                'password' => Hash::make(Str::random(40)),
                'registered_via' => 'invite',
                'invitation_token' => $invitationToken,
                'invitation_sent_at' => now(),
            ]);

            $tenant->increment('current_user_count');

            $user = $user->fresh(['division', 'department', 'team']);

            $inviter = $request->user();
            $user->notify(new MemberInvitationNotification(
                $tenant,
                $invitationToken,
                $inviter->name
            ));

            return $user;
        });

        return response()->json([
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'division_id' => $user->division_id,
                'department_id' => $user->department_id,
                'team_id' => $user->team_id,
            ],
        ], 201);
    }

    private function assertHierarchyMatchesRole(string $role, ?Division $division, ?Department $department, ?Team $team): void
    {
        if ($role === 'division_lead' && ! $division) {
            abort(422, '事業群主管必須指定所屬事業群。');
        }

        if ($role === 'department_manager' && ! $department) {
            abort(422, '部門主管必須指定所屬部門。');
        }

        if ($role === 'team_lead' && ! $team) {
            abort(422, '小組主管必須指定所屬小組。');
        }
    }

    private function assertLevelManagerCanInvite(User $user, ?Division $division, ?Department $department, ?Team $team): void
    {
        // Division lead can only invite to their division or below
        if ($user->hasRole('division_lead')) {
            if ($division && $user->division_id !== $division->id) {
                abort(403, '您只能邀請到您所管理的事業群或其下層級。');
            }
            if ($department && $department->division_id !== $user->division_id) {
                abort(403, '您只能邀請到您所管理的事業群或其下層級。');
            }
            if ($team && $team->division_id !== $user->division_id) {
                abort(403, '您只能邀請到您所管理的事業群或其下層級。');
            }
        }

        // Department manager can only invite to their department or below
        if ($user->hasRole('department_manager')) {
            if ($department && $user->department_id !== $department->id) {
                abort(403, '您只能邀請到您所管理的部門或其下層級。');
            }
            if ($team && $team->department_id !== $user->department_id) {
                abort(403, '您只能邀請到您所管理的部門或其下層級。');
            }
            if ($division && $division->id !== $user->division_id) {
                abort(403, '您只能邀請到您所管理的部門或其下層級。');
            }
        }

        // Team lead can only invite to their team
        if ($user->hasRole('team_lead')) {
            if ($team && $user->team_id !== $team->id) {
                abort(403, '您只能邀請到您所管理的小組。');
            }
            if ($department && $department->id !== $user->department_id) {
                abort(403, '您只能邀請到您所管理的小組。');
            }
            if ($division && $division->id !== $user->division_id) {
                abort(403, '您只能邀請到您所管理的小組。');
            }
        }
    }
}
