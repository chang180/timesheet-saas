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
        $branding = $company->branding ?? [];
        $settings = $company->settings;

        return Inertia::render('tenant/settings/index', [
            'settings' => [
                'companyName' => $company->name,
                'brandColor' => $branding['color'] ?? null,
                'logo' => $branding['logo'] ?? null,
                'welcomePage' => $settings?->welcome_page ?? [],
                'ipWhitelist' => $settings?->login_ip_whitelist ?? [],
                'maxUserLimit' => $company->user_limit,
                'currentUserCount' => $company->users()->count(),
            ],
        ]);
    }

    public function updateWelcomePage(UpdateWelcomePageRequest $request): RedirectResponse
    {
        $company = auth()->user()->company;

        $company->settings()->updateOrCreate(
            ['company_id' => $company->id],
            ['welcome_page' => $request->validated()]
        );

        return redirect()->back()->with('success', '歡迎頁設定已更新');
    }

    public function updateIPWhitelist(UpdateIPWhitelistRequest $request): RedirectResponse
    {
        $company = auth()->user()->company;

        $company->settings()->updateOrCreate(
            ['company_id' => $company->id],
            ['login_ip_whitelist' => $request->validated()['ipAddresses']]
        );

        return redirect()->back()->with('success', 'IP 白名單已更新');
    }
}
