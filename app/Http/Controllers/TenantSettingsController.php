<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\UpdateBrandingRequest;
use App\Http\Requests\Tenant\UpdateWelcomePageRequest;
use App\Http\Requests\UpdateIPWhitelistRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function index(Company $company): Response
    {
        $this->authorize('update', $company);

        $branding = $company->branding ?? [];
        $brandColor = $branding['color']
            ?? $branding['primaryColor']
            ?? null;
        $brandLogo = $branding['logo']
            ?? $branding['logoUrl']
            ?? null;
        $settings = $company->settings;

        return Inertia::render('tenant/settings/index', [
            'settings' => [
                'companyName' => $company->name,
                'companySlug' => $company->slug,
                'brandColor' => $brandColor,
                'logo' => $brandLogo,
                'welcomePage' => $settings?->welcome_page ?? [],
                'ipWhitelist' => $settings?->login_ip_whitelist ?? [],
                'maxUserLimit' => $company->user_limit,
                'currentUserCount' => $company->users()->count(),
            ],
        ]);
    }

    public function updateWelcomePage(UpdateWelcomePageRequest $request, Company $company): RedirectResponse
    {
        $company->settings()->updateOrCreate(
            ['company_id' => $company->id],
            ['welcome_page' => $request->validated()]
        );

        if ($company->onboarded_at === null) {
            $company->forceFill(['onboarded_at' => now()])->save();
        }

        return redirect()->back()->with('success', '歡迎頁設定已更新');
    }

    public function updateIPWhitelist(UpdateIPWhitelistRequest $request, Company $company): RedirectResponse
    {
        $company->settings()->updateOrCreate(
            ['company_id' => $company->id],
            ['login_ip_whitelist' => $request->whitelist()]
        );

        if ($company->onboarded_at === null) {
            $company->forceFill(['onboarded_at' => now()])->save();
        }

        return redirect()->back()->with('success', 'IP 白名單已更新');
    }

    public function updateBranding(UpdateBrandingRequest $request, Company $company): RedirectResponse
    {
        $branding = $request->validated('branding');

        $company->forceFill([
            'branding' => array_filter([
                'color' => $branding['color'] ?? null,
                'logo' => $branding['logo'] ?? null,
            ], fn ($value) => $value !== null),
        ])->save();

        if ($company->onboarded_at === null) {
            $company->forceFill(['onboarded_at' => now()])->save();
        }

        return redirect()->back()->with('success', '品牌設定已更新');
    }
}
