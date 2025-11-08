<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request, Company $company): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        $company->loadMissing([
            'settings',
            'divisions' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'name', 'slug', 'is_active'),
            'departments' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'name', 'slug', 'is_active'),
            'teams' => fn ($query) => $query->orderBy('sort_order')->select('id', 'company_id', 'division_id', 'department_id', 'name', 'slug', 'is_active'),
        ]);

        $settings = $company->settings()->firstOrCreate([]);

        return response()->json([
            'company' => [
                'name' => $company->name,
                'slug' => $company->slug,
                'status' => $company->status,
                'branding' => $company->branding,
                'timezone' => $company->timezone,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
            ],
            'settings' => [
                'welcome_page' => $settings->welcome_page ?? [],
                'login_ip_whitelist' => $settings->login_ip_whitelist ?? [],
                'notification_preferences' => $settings->notification_preferences ?? [],
                'default_weekly_report_modules' => $settings->default_weekly_report_modules ?? [],
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
