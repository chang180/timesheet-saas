<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InviteMemberRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberInviteController extends Controller
{
    public function store(InviteMemberRequest $request, Company $company): JsonResponse
    {
        if ($company->current_user_count >= $company->user_limit) {
            abort(422, __('The member limit has been reached for this tenant.'));
        }

        $email = $request->invitedEmail();

        if (User::query()->where('email', $email)->exists()) {
            abort(422, __('This email address is already in use.'));
        }

        $data = $request->validated();

        $division = isset($data['division_id']) ? Division::find($data['division_id']) : null;
        $department = isset($data['department_id']) ? Department::find($data['department_id']) : null;
        $team = isset($data['team_id']) ? Team::find($data['team_id']) : null;

        if ($team && $department && $team->department_id !== $department->id) {
            abort(422, __('Selected team does not belong to the provided department.'));
        }

        if ($team && ! $department) {
            $department = $team->department;
            $data['department_id'] = $department?->id;
        }

        if ($department && $division && $department->division_id !== $division->id) {
            abort(422, __('Selected department does not belong to the provided division.'));
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        $this->assertHierarchyMatchesRole($request->invitedRole(), $division, $department, $team);

        $user = DB::transaction(function () use ($request, $company, $division, $department, $team, $email): User {
            $user = User::create([
                'company_id' => $company->getKey(),
                'division_id' => $division?->id,
                'department_id' => $department?->id,
                'team_id' => $team?->id,
                'name' => $request->invitedName(),
                'email' => $email,
                'role' => $request->invitedRole(),
                'password' => Hash::make(Str::random(40)),
                'registered_via' => 'invite',
            ]);

            $company->increment('current_user_count');

            return $user->fresh(['division', 'department', 'team']);
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
            abort(422, __('Division leads must be assigned to a division.'));
        }

        if ($role === 'department_manager' && ! $department) {
            abort(422, __('Department managers must be assigned to a department.'));
        }

        if ($role === 'team_lead' && ! $team) {
            abort(422, __('Team leads must be assigned to a team.'));
        }
    }
}
