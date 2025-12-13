<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithCompany(array $attributes = [], array $companyAttributes = []): User
{
    $company = Company::factory()->create(array_merge([
        'onboarded_at' => now(),
    ], $companyAttributes));
    CompanySetting::factory()->for($company)->create();

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ], $attributes));
}

it('can login and create weekly report', function () {
    $user = createUserWithCompany(['role' => 'member']);

    $page = visit(route('login'));

    $page->assertSee('登入週報通')
        ->assertNoJavascriptErrors()
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('登入')
        ->assertNoJavascriptErrors();

    // 等待導航完成
    $page->wait(1);

    // 導航到週報列表
    $page->navigate(route('tenant.weekly-reports', $user->company))
        ->assertSee('週報工作簿')
        ->assertNoJavascriptErrors();

    // 建立新週報
    $page->click('@create-weekly-report')
        ->assertSee('建立週報草稿')
        ->assertNoJavascriptErrors();

    // 填寫週報摘要
    $page->fill('@summary', '本週完成重要功能開發')
        ->assertNoJavascriptErrors();

    // 新增本週完成事項
    $page->click('新增項目')
        ->wait(0.5)
        ->assertNoJavascriptErrors();

    // 填寫第一個項目
    $page->fill('@current_week.0.title', '實作週報功能')
        ->fill('@current_week.0.content', '完成週報表單與列表頁面')
        ->fill('@current_week.0.hours_spent', '8')
        ->assertNoJavascriptErrors();

    // 新增下週預計事項
    $page->click('新增項目')
        ->wait(0.5)
        ->assertNoJavascriptErrors();

    // 填寫下週項目
    $page->fill('@next_week.0.title', '優化週報介面')
        ->fill('@next_week.0.content', '改善使用者體驗')
        ->fill('@next_week.0.planned_hours', '6')
        ->assertNoJavascriptErrors();

    // 檢查工時統計顯示
    $page->assertSee('本週完成總工時')
        ->assertSee('8.0 小時')
        ->assertSee('下週預計總工時')
        ->assertSee('6.0 小時')
        ->assertNoJavascriptErrors();

    // 儲存週報
    $page->click('@save-weekly-report')
        ->wait(1)
        ->assertNoJavascriptErrors();

    // 驗證週報已建立
    $this->assertDatabaseHas('weekly_reports', [
        'company_id' => $user->company->id,
        'user_id' => $user->id,
        'summary' => '本週完成重要功能開發',
    ]);
});

it('can view weekly report list after creating report', function () {
    $user = createUserWithCompany(['role' => 'member']);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'summary' => '測試週報摘要',
        ]);

    $page = visit(route('login'))
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('登入')
        ->navigate(route('tenant.weekly-reports', $company));

    $page->assertSee('週報工作簿')
        ->assertSee('測試週報摘要')
        ->assertSee('草稿')
        ->assertNoJavascriptErrors();

    // 點擊編輯按鈕
    $page->click("@edit-report-{$report->id}")
        ->assertSee('編輯週報')
        ->assertSee('測試週報摘要')
        ->assertNoJavascriptErrors();
});

it('can edit existing weekly report', function () {
    $user = createUserWithCompany(['role' => 'member']);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'summary' => '原始摘要',
        ]);

    $page = visit(route('login'))
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('登入')
        ->navigate(route('tenant.weekly-reports.edit', [$company, $report]));

    $page->assertSee('編輯週報')
        ->assertSee('原始摘要')
        ->assertNoJavascriptErrors();

    // 更新摘要
    $page->fill('summary', '更新後的摘要')
        ->click('@save-weekly-report')
        ->assertSee('已更新')
        ->assertNoJavascriptErrors();

    // 驗證更新
    $this->assertDatabaseHas('weekly_reports', [
        'id' => $report->id,
        'summary' => '更新後的摘要',
    ]);
});

it('shows total hours calculation in form', function () {
    $user = createUserWithCompany(['role' => 'member']);
    $company = $user->company;

    $page = visit(route('login'))
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('登入')
        ->wait(1)
        ->navigate(route('tenant.weekly-reports.create', $company));

    // 新增多個項目並檢查工時統計
    $page->click('新增項目')
        ->wait(0.5)
        ->fill('@current_week.0.hours_spent', '5')
        ->click('新增項目')
        ->wait(0.5)
        ->fill('@current_week.1.hours_spent', '3.5')
        ->wait(0.5)
        ->assertSee('本週完成總工時')
        ->assertSee('8.5 小時')
        ->assertSee('2 個項目')
        ->assertNoJavascriptErrors();
});
