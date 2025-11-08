<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateMemberRoleRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class MemberRoleController extends Controller
{
    public function update(UpdateMemberRoleRequest $request, Company $company, User $member): JsonResponse
    {
        if (! $member->belongsToCompany($company->getKey())) {
            abort(404);
        }

        if ($member->isHqAdmin()) {
            abort(422, __('HQ administrators cannot be modified from tenant routes.'));
        }

        $data = $request->payload();

        $division = $data['division_id'] ? Division::find($data['division_id']) : null;
        $department = $data['department_id'] ? Department::find($data['department_id']) : null;
        $team = $data['team_id'] ? Team::find($data['team_id']) : null;

        if ($team && $department && $team->department_id !== $department->id) {
            abort(422, __('The selected team does not belong to the provided department.'));
        }

        if ($team && ! $department) {
            $department = $team->department;
            $data['department_id'] = $department?->id;
        }

        if ($department && $division && $department->division_id !== $division->id) {
            abort(422, __('The selected department does not belong to the provided division.'));
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        $this->assertHierarchyMatchesRole($data['role'], $division, $department, $team);

        $member->forceFill([
            'role' => $data['role'],
            'division_id' => $division?->id,
            'department_id' => $department?->id,
            'team_id' => $team?->id,
        ])->save();

        return response()->json([
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'division_id' => $member->division_id,
                'department_id' => $member->department_id,
                'team_id' => $member->team_id,
            ],
        ]);
    }

    private function assertHierarchyMatchesRole(string $role, ?Division $division, ?Department $department, ?Team $team): void
    {
        switch ($role) {
            case 'division_lead':
                if (! $division) {
                    abort(422, __('Division leads must be assigned to a division.'));
                }

                break;
            case 'department_manager':
                if (! $department) {
                    abort(422, __('Department managers must be assigned to a department.'));
                }

                break;
            case 'team_lead':
                if (! $team) {
                    abort(422, __('Team leads must be assigned to a team.'));
                }

                break;
        }
    }
}
