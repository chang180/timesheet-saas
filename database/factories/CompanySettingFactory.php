<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'welcome_page' => [
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
                    ],
                ],
                'announcements' => ['enabled' => false, 'items' => []],
                'supportContacts' => ['enabled' => false, 'contacts' => []],
                'ctas' => [],
            ],
            'login_ip_whitelist' => [],
            'notification_preferences' => [],
            'default_weekly_report_modules' => [],
        ];
    }
}
