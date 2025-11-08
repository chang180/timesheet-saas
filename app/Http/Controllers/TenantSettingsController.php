<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIPWhitelistRequest;
use App\Http\Requests\UpdateWelcomePageRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function index(): Response
    {
        $company = auth()->user()->company;

        return Inertia::render('tenant/settings/index', [
            'settings' => [
                'companyName' => $company->name,
                'brandColor' => $company->brand_color,
                'logo' => $company->logo,
                'welcomePage' => $company->settings['welcome_page'] ?? [],
                'ipWhitelist' => $company->settings['ip_whitelist'] ?? [],
                'workingHoursPerDay' => $company->settings['working_hours_per_day'] ?? 8,
                'maxUserLimit' => $company->user_limit,
                'currentUserCount' => $company->users()->count(),
            ],
        ]);
    }

    public function updateWelcomePage(UpdateWelcomePageRequest $request): RedirectResponse
    {
        $company = auth()->user()->company;

        $settings = $company->settings ?? [];
        $settings['welcome_page'] = $request->validated();

        $company->update(['settings' => $settings]);

        return redirect()->back()->with('success', '歡迎頁設定已更新');
    }

    public function updateIPWhitelist(UpdateIPWhitelistRequest $request): RedirectResponse
    {
        $company = auth()->user()->company;

        $settings = $company->settings ?? [];
        $settings['ip_whitelist'] = $request->validated()['ipAddresses'];

        $company->update(['settings' => $settings]);

        return redirect()->back()->with('success', 'IP 白名單已更新');
    }
}
