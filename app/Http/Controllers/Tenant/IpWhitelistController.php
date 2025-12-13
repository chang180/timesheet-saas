<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateIpWhitelistRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class IpWhitelistController extends Controller
{
    public function update(UpdateIpWhitelistRequest $request, Company $company): JsonResponse
    {
        $settings = $company->settings()->firstOrCreate([]);

        $settings->update([
            'login_ip_whitelist' => $request->whitelist(),
        ]);

        if ($company->onboarded_at === null) {
            $company->forceFill(['onboarded_at' => now()])->save();
        }

        return response()->json([
            'login_ip_whitelist' => $settings->login_ip_whitelist ?? [],
        ]);
    }
}
