<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Display the organization management page.
     */
    public function index(Request $request, Company $company): Response
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        if (! $user->isCompanyAdmin()) {
            abort(403, __('Only company administrators can manage organization.'));
        }

        $settings = $company->settings()->firstOrCreate([]);
        $enabledLevels = $settings->getEnabledLevels();

        $company->loadMissing([
            'divisions' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'name', 'slug', 'description', 'sort_order', 'is_active', 'invitation_token', 'invitation_enabled'),
            'departments' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'name', 'slug', 'description', 'sort_order', 'is_active', 'invitation_token', 'invitation_enabled'),
            'teams' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'department_id', 'name', 'slug', 'description', 'sort_order', 'is_active', 'invitation_token', 'invitation_enabled'),
        ]);

        // Filter organizations based on enabled levels
        $divisions = $enabledLevels && in_array('division', $enabledLevels, true) ? $company->divisions : collect();
        $departments = $enabledLevels && in_array('department', $enabledLevels, true) ? $company->departments : collect();
        $teams = $enabledLevels && in_array('team', $enabledLevels, true) ? $company->teams : collect();

        return Inertia::render('tenant/organization/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
            ],
            'organization_levels' => $enabledLevels,
            'organization' => [
                'divisions' => $divisions->map(fn ($division) => [
                    'id' => $division->id,
                    'name' => $division->name,
                    'slug' => $division->slug,
                    'description' => $division->description,
                    'sort_order' => $division->sort_order,
                    'is_active' => (bool) $division->is_active,
                    'invitation_token' => $division->invitation_token,
                    'invitation_enabled' => (bool) $division->invitation_enabled,
                ]),
                'departments' => $departments->map(fn ($department) => [
                    'id' => $department->id,
                    'division_id' => $department->division_id,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'description' => $department->description,
                    'sort_order' => $department->sort_order,
                    'is_active' => (bool) $department->is_active,
                    'invitation_token' => $department->invitation_token,
                    'invitation_enabled' => (bool) $department->invitation_enabled,
                ]),
                'teams' => $teams->map(fn ($team) => [
                    'id' => $team->id,
                    'division_id' => $team->division_id,
                    'department_id' => $team->department_id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'description' => $team->description,
                    'sort_order' => $team->sort_order,
                    'is_active' => (bool) $team->is_active,
                    'invitation_token' => $team->invitation_token,
                    'invitation_enabled' => (bool) $team->invitation_enabled,
                ]),
            ],
        ]);
    }
}
