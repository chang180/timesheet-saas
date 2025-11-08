<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateWelcomePageRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class WelcomePageController extends Controller
{
    public function update(UpdateWelcomePageRequest $request, Company $company): JsonResponse
    {
        $settings = $company->settings()->firstOrCreate([]);

        $settings->update([
            'welcome_page' => $request->welcomePagePayload(),
        ]);

        if ($company->onboarded_at === null) {
            $company->forceFill(['onboarded_at' => now()])->save();
        }

        return response()->json([
            'welcome_page' => $settings->welcome_page,
        ]);
    }
}
