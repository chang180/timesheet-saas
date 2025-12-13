<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ListMembersRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of members (API).
     */
    public function index(ListMembersRequest $request, Company $company): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            Log::warning('User not allowed to access tenant', [
                'user_id' => $user?->id,
                'user_company_id' => $user?->company_id,
                'requested_company_id' => $company->getKey(),
            ]);
            abort(403, __('You are not allowed to access this tenant.'));
        }

        $query = User::query()
            ->where('company_id', $company->getKey())
            ->with(['division', 'department', 'team']);

        if ($user->isCompanyAdmin()) {
            // 公司管理者可查看所有成員
        } elseif ($user->hasRole('division_lead') && $user->division_id) {
            // Division 主管可查看所屬 division 下的所有成員
            $query->where(function ($q) use ($user) {
                $q->where('division_id', $user->division_id)
                    ->orWhereHas('department', function ($q) use ($user) {
                        $q->where('division_id', $user->division_id);
                    })
                    ->orWhereHas('team', function ($q) use ($user) {
                        $q->where('division_id', $user->division_id);
                    });
            });
        } elseif ($user->hasRole('department_manager') && $user->department_id) {
            // Department 主管可查看所屬 department 下的所有成員
            $query->where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->orWhereHas('team', function ($q) use ($user) {
                        $q->where('department_id', $user->department_id);
                    });
            });
        } elseif ($user->hasRole('team_lead') && $user->team_id) {
            // Team 主管可查看所屬 team 下的所有成員
            $query->where('team_id', $user->team_id);
        } else {
            // 一般成員無法查看其他成員列表
            Log::warning('User does not have permission to view members', [
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);
            abort(403, __('You do not have permission to view members.'));
        }

        $filters = $request->validated();

        if (isset($filters['role']) && $filters['role'] !== '' && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['division_id']) && $filters['division_id'] !== '' && $filters['division_id'] !== 'all') {
            $query->where('division_id', $filters['division_id']);
        }

        if (isset($filters['department_id']) && $filters['department_id'] !== '' && $filters['department_id'] !== 'all') {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['team_id']) && $filters['team_id'] !== '' && $filters['team_id'] !== 'all') {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $members = $query->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'members' => $members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'division' => $member->division ? [
                    'id' => $member->division->id,
                    'name' => $member->division->name,
                ] : null,
                'department' => $member->department ? [
                    'id' => $member->department->id,
                    'name' => $member->department->name,
                ] : null,
                'team' => $member->team ? [
                    'id' => $member->team->id,
                    'name' => $member->team->name,
                ] : null,
                'last_active_at' => $member->last_active_at?->toIso8601String(),
                'invitation_sent_at' => $member->invitation_sent_at?->toIso8601String(),
                'invitation_accepted_at' => $member->invitation_accepted_at?->toIso8601String(),
            ])->values()->all(),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    /**
     * Display the members management page (Inertia).
     */
    public function show(Request $request, Company $company): Response
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin()) {
            abort(403, __('Only company administrators can manage members.'));
        }

        $company->loadMissing([
            'divisions' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'name', 'slug', 'is_active'),
            'departments' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'name', 'slug', 'is_active'),
            'teams' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'department_id', 'name', 'slug', 'is_active'),
        ]);

        return Inertia::render('tenant/members/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
            ],
            'organization' => [
                'divisions' => $company->divisions->map(fn ($division) => [
                    'id' => $division->id,
                    'name' => $division->name,
                    'slug' => $division->slug,
                    'is_active' => (bool) $division->is_active,
                ]),
                'departments' => $company->departments->map(fn ($department) => [
                    'id' => $department->id,
                    'division_id' => $department->division_id,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'is_active' => (bool) $department->is_active,
                ]),
                'teams' => $company->teams->map(fn ($team) => [
                    'id' => $team->id,
                    'division_id' => $team->division_id,
                    'department_id' => $team->department_id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'is_active' => (bool) $team->is_active,
                ]),
            ],
            'roles' => [
                'available' => User::tenantAssignableRoles(),
            ],
        ]);
    }
}
