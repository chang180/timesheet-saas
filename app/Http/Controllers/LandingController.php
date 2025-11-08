<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function global(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user) {
            $company = $user->company;
            $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);

            if ($company) {
                if ($isManager && $company->onboarded_at === null) {
                    return redirect()->route('tenant.settings', $company);
                }

                return redirect()->route('tenant.weekly-reports', $company);
            }
        }

        $demoTenantConfig = config('landing.demo_tenant', []);

        return Inertia::render('landing/global-landing', [
            'demoTenant' => [
                'enabled' => (bool) ($demoTenantConfig['enabled'] ?? false),
                'name' => $demoTenantConfig['name'] ?? null,
                'url' => $demoTenantConfig['url'] ?? null,
                'description' => $demoTenantConfig['description'] ?? null,
            ],
        ]);
    }

    public function tenant(Company $company): Response
    {
        $user = auth()->user();

        if (! $user || $user->company_id !== $company->id) {
            abort(403, '無權存取此用戶。');
        }

        $branding = $company->branding ?? [];

        $tenantSettings = [
            'companyName' => $company->name,
            'brandColor' => $branding['color'] ?? null,
            'logo' => $branding['logo'] ?? null,
        ];

        $welcomeConfig = $company->settings?->welcome_page ?? $this->getDefaultWelcomeConfig();

        return Inertia::render('landing/tenant-welcome', [
            'tenantSettings' => $tenantSettings,
            'welcomeConfig' => $welcomeConfig,
        ]);
    }

    private function getDefaultWelcomeConfig(): array
    {
        return [
            'hero' => [
                'enabled' => true,
                'title' => '歡迎使用週報通',
                'subtitle' => '簡化您的週報管理流程',
            ],
            'quickStartSteps' => [
                'enabled' => true,
                'steps' => [
                    ['title' => '登入系統', 'description' => '使用您的帳號登入'],
                    ['title' => '填寫週報', 'description' => '記錄本週工作與下週計畫'],
                    ['title' => '提交審核', 'description' => '提交週報供主管查看'],
                ],
            ],
            'weeklyReportDemo' => [
                'enabled' => true,
                'highlights' => [
                    '拖曳排序同步更新主管檢視順序',
                    'Redmine/Jira 連動，自動帶入任務與工時',
                    '假日工時超額即時提醒',
                ],
            ],
            'announcements' => [
                'enabled' => false,
                'items' => [],
            ],
            'supportContacts' => [
                'enabled' => false,
                'contacts' => [],
            ],
            'ctas' => [],
        ];
    }
}
