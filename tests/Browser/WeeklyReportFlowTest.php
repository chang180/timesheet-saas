<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

if (! function_exists('createUserWithCompany')) {
    function createUserWithCompany(array $attributes = [], array $companyAttributes = []): User
    {
        $company = Company::factory()->create(array_merge([
            'onboarded_at' => now(),
        ], $companyAttributes));
        CompanySetting::factory()->for($company)->create();

        return User::factory()->withoutTwoFactor()->create(array_merge([
            'company_id' => $company->id,
            'email' => 'test@example.com',
            'password' => bcrypt('TestPassword123!@#'),
        ], $attributes));
    }
}

it('can login and navigate to create weekly report page', function () {
    $user = createUserWithCompany(['role' => 'member']);
    $company = $user->company;

    $page = visit(route('tenant.auth', $company));

    $page->assertSee('登入帳號')
        ->assertNoJavascriptErrors()
        ->fill('email', 'test@example.com')
        ->fill('password', 'TestPassword123!@#')
        ->click('button[type="submit"]')
        ->assertNoJavascriptErrors();

    // 等待登入導航完成並自動重導向到週報列表
    $page->wait(3)
        ->assertSee('週報工作簿')
        ->assertNoJavascriptErrors();

    // 點擊建立新週報
    $page->click('@goto-weekly-report-create')
        ->wait(1)
        ->assertSee('建立週報草稿')
        ->assertNoJavascriptErrors();

    // 驗證表單區塊存在
    $page->assertSee('本週完成事項')
        ->assertSee('下週預計事項')
        ->assertSee('工時統計')
        ->assertSee('摘要')
        ->assertNoJavascriptErrors();
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

    $page = visit(route('tenant.auth', $company))
        ->fill('email', 'test@example.com')
        ->fill('password', 'TestPassword123!@#')
        ->click('button[type="submit"]')
        ->wait(3);

    // 登入後應該看到週報列表
    $page->assertSee('週報工作簿')
        ->assertSee('測試週報摘要')
        ->assertSee('草稿')
        ->assertNoJavascriptErrors();

    // 點擊編輯按鈕
    $page->click("@edit-report-{$report->id}")
        ->wait(1)
        ->assertSee('編輯週報')
        ->assertNoJavascriptErrors();
});

it('can navigate to edit existing weekly report', function () {
    $user = createUserWithCompany(['role' => 'member']);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'summary' => '測試摘要內容',
        ]);

    // 登入
    $page = visit(route('tenant.auth', $company))
        ->fill('email', 'test@example.com')
        ->fill('password', 'TestPassword123!@#')
        ->click('button[type="submit"]')
        ->wait(3);

    // 點擊編輯按鈕
    $page->click("@edit-report-{$report->id}")
        ->wait(1)
        ->assertSee('編輯週報')
        ->assertSee('測試摘要內容')
        ->assertNoJavascriptErrors();

    // 驗證表單區塊存在
    $page->assertSee('本週完成事項')
        ->assertSee('下週預計事項')
        ->assertSee('工時統計')
        ->assertSee('摘要')
        ->assertNoJavascriptErrors();
});

it('shows hours statistics in form', function () {
    $user = createUserWithCompany(['role' => 'member']);
    $company = $user->company;

    $page = visit(route('tenant.auth', $company))
        ->fill('email', 'test@example.com')
        ->fill('password', 'TestPassword123!@#')
        ->click('button[type="submit"]')
        ->wait(3);

    // 點擊建立新週報
    $page->click('@goto-weekly-report-create')
        ->wait(1)
        ->assertSee('建立週報草稿')
        ->assertNoJavascriptErrors();

    // 驗證工時統計區塊存在
    $page->assertSee('工時統計')
        ->assertSee('本週完成總工時')
        ->assertSee('下週預計總工時')
        ->assertNoJavascriptErrors();
});
