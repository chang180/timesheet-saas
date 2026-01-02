<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateOrganizationLevelsRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationLevelsController extends Controller
{
    /**
     * Get organization levels setting.
     */
    public function show(Request $request, Company $company): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->belongsToCompany($company->getKey())) {
            abort(403, __('You are not allowed to access this tenant.'));
        }

        $settings = $company->settings()->firstOrCreate([]);
        $levels = $settings->getEnabledLevels();

        return response()->json([
            'organization_levels' => $levels,
        ]);
    }

    /**
     * Update organization levels setting.
     */
    public function update(UpdateOrganizationLevelsRequest $request, Company $company): JsonResponse
    {
        $settings = $company->settings()->firstOrCreate([]);
        $settings->update([
            'organization_levels' => $request->validated('organization_levels'),
        ]);

        return response()->json([
            'message' => '組織層級設定已更新',
            'organization_levels' => $settings->getEnabledLevels(),
        ]);
    }
}
