<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function global(): Response
    {
        return Inertia::render('landing/global-landing');
    }

    public function tenant(): Response
    {
        $company = auth()->user()?->company;

        if (! $company) {
            abort(404, '找不到租戶資訊');
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
