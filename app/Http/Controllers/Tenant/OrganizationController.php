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

        $company->loadMissing([
            'divisions' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'name', 'slug', 'description', 'sort_order', 'is_active'),
            'departments' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'name', 'slug', 'description', 'sort_order', 'is_active'),
            'teams' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'department_id', 'name', 'slug', 'description', 'sort_order', 'is_active'),
        ]);

        return Inertia::render('tenant/organization/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
            ],
            'organization' => [
                'divisions' => $company->divisions->map(fn ($division) => [
                    'id' => $division->id,
                    'name' => $division->name,
                    'slug' => $division->slug,
                    'description' => $division->description,
                    'sort_order' => $division->sort_order,
                    'is_active' => (bool) $division->is_active,
                ]),
                'departments' => $company->departments->map(fn ($department) => [
                    'id' => $department->id,
                    'division_id' => $department->division_id,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'description' => $department->description,
                    'sort_order' => $department->sort_order,
                    'is_active' => (bool) $department->is_active,
                ]),
                'teams' => $company->teams->map(fn ($team) => [
                    'id' => $team->id,
                    'division_id' => $team->division_id,
                    'department_id' => $team->department_id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'description' => $team->description,
                    'sort_order' => $team->sort_order,
                    'is_active' => (bool) $team->is_active,
                ]),
            ],
        ]);
    }
}
