<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createUserWithCompany(array $attributes = [], array $companyAttributes = []): User
{
    $company = Company::factory()->create($companyAttributes);
    CompanySetting::factory()->for($company)->create();

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
    ], $attributes));
}

it('redirects manager to settings when company not onboarded', function () {
    $user = createUserWithCompany(['role' => 'company_admin'], ['onboarded_at' => null]);

    $response = actingAs($user)->get(route('tenant.weekly-reports', $user->company));

    $response->assertRedirect(route('tenant.settings', $user->company));
});

it('renders weekly report list with company data', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
        ]);

    $response = actingAs($user)->get(route('tenant.weekly-reports', $company));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('weekly/list')
            ->where('company.slug', $company->slug)
            ->where('reports.0.id', $report->id)
        );
});

it('prefills create page with previous next week plans', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $response = actingAs($user)->get(route('tenant.weekly-reports.create', $company));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('weekly/form')
            ->where('company.slug', $company->slug)
        );

    $previousWeek = now()->subWeek();

    $previousReport = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'division_id' => $user->division_id,
        'department_id' => $user->department_id,
        'team_id' => $user->team_id,
        'work_year' => (int) $previousWeek->isoFormat('GGGG'),
        'work_week' => (int) $previousWeek->isoWeek(),
        'status' => WeeklyReport::STATUS_SUBMITTED,
    ]);

    WeeklyReportItem::factory()
        ->for($previousReport)
        ->nextWeek()
        ->state([
            'title' => '準備 API 試跑',
            'planned_hours' => 5,
        ])
        ->create();

    $response = actingAs($user)->get(route('tenant.weekly-reports.create', $company));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('weekly/form')
            ->where('prefill.currentWeek.0.title', '準備 API 試跑')
            ->where('prefill.currentWeek.0.hours_spent', 0) // 實際工時初始為0
            ->where('prefill.currentWeek.0.planned_hours', 5) // 預計工時從上週帶入
        );
});

it('redirects to existing report when same week already exists', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $existing = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
        ]);

    $response = actingAs($user)->post(route('tenant.weekly-reports.store', $company), [
        'work_year' => now()->isoFormat('GGGG'),
        'work_week' => now()->isoWeek(),
        'summary' => 'test',
        'current_week' => [],
        'next_week' => [],
    ]);

    $response->assertRedirect(route('tenant.weekly-reports.edit', [$company, $existing]));
});

it('stores weekly report and items', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $payload = [
        'work_year' => now()->isoFormat('GGGG'),
        'work_week' => now()->isoWeek(),
        'summary' => '這週主要完成登入模組改版',
        'current_week' => [
            [
                'title' => '登入 API 調整',
                'content' => '支援多租戶 slug',
                'hours_spent' => 6.5,
                'issue_reference' => 'JIRA-9001',
                'is_billable' => true,
                'tags' => ['backend'],
            ],
        ],
        'next_week' => [
            [
                'title' => '表單驗證提速',
                'content' => '研究 memoized schema',
                'planned_hours' => 4.0,
                'tags' => ['frontend'],
            ],
        ],
    ];

    $response = actingAs($user)->post(route('tenant.weekly-reports.store', $company), $payload);

    $response->assertRedirect();

    $report = WeeklyReport::query()->where('company_id', $company->id)->where('user_id', $user->id)->first();
    expect($report)->not->toBeNull();
    expect($report->items()->count())->toBe(2);
    expect($report->items()->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)->first()->title)->toBe('登入 API 調整');
    expect($report->items()->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)->first()->planned_hours)->toBe(4.0);
});

it('updates weekly report items', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
        ]);

    WeeklyReportItem::factory()->for($report)->create([
        'title' => '原始項目',
        'hours_spent' => 3,
    ]);

    $payload = [
        'work_year' => $report->work_year,
        'work_week' => $report->work_week,
        'summary' => '更新週報內容',
        'current_week' => [
            [
                'title' => '調整授權流程',
                'content' => '新增部門審批',
                'hours_spent' => 5,
                'issue_reference' => null,
                'is_billable' => false,
                'tags' => [],
            ],
        ],
        'next_week' => [
            [
                'title' => '導入報表下載',
                'content' => '研究 chunk download',
                'planned_hours' => 6,
                'tags' => ['frontend'],
            ],
        ],
    ];

    $response = actingAs($user)->put(route('tenant.weekly-reports.update', [$company, $report]), $payload);

    $response->assertRedirect(route('tenant.weekly-reports.edit', [$company, $report]));

    $report->refresh();
    expect($report->items()->count())->toBe(2);
    expect($report->items()->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)->first()->title)->toBe('調整授權流程');
    expect($report->summary)->toBe('更新週報內容');
});

it('stores weekly report with planned_hours for current week items', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $payload = [
        'work_year' => now()->isoFormat('GGGG'),
        'work_week' => now()->isoWeek(),
        'summary' => '測試預計工時',
        'current_week' => [
            [
                'title' => '功能開發',
                'content' => '實作新功能',
                'hours_spent' => 8.0,
                'planned_hours' => 6.0,
                'tags' => [],
            ],
        ],
        'next_week' => [],
    ];

    $response = actingAs($user)->post(route('tenant.weekly-reports.store', $company), $payload);

    $response->assertRedirect();

    $report = WeeklyReport::query()->where('company_id', $company->id)->where('user_id', $user->id)->first();
    $currentWeekItem = $report->items()->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)->first();
    expect($currentWeekItem->planned_hours)->toBe(6.0);
    expect($currentWeekItem->hours_spent)->toBe(8.0);
});

it('submits weekly report and updates status', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'status' => WeeklyReport::STATUS_DRAFT,
        ]);

    $response = actingAs($user)->post(route('tenant.weekly-reports.submit', [$company, $report]));

    $response->assertRedirect(route('tenant.weekly-reports.edit', [$company, $report]));

    $report->refresh();
    expect($report->status)->toBe(WeeklyReport::STATUS_SUBMITTED);
    expect($report->submitted_at)->not->toBeNull();
    expect($report->submitted_by)->toBe($user->id);
});

it('prevents submitting non-draft weekly report', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'status' => WeeklyReport::STATUS_SUBMITTED,
        ]);

    $response = actingAs($user)->post(route('tenant.weekly-reports.submit', [$company, $report]));

    $response->assertRedirect(route('tenant.weekly-reports.edit', [$company, $report]));
    $response->assertSessionHas('warning');
});

it('allows manager to reopen submitted weekly report', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create();

    // 建立部門管理者
    $manager = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    // 建立一般成員
    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    // 建立已送出的週報
    $report = WeeklyReport::factory()
        ->forCompany($company, $member)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'status' => WeeklyReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => $member->id,
        ]);

    // 管理者重新開啟週報
    $response = actingAs($manager)->post(route('tenant.weekly-reports.reopen', [$company, $report]));

    $response->assertRedirect(route('tenant.weekly-reports.edit', [$company, $report]));
    $response->assertSessionHas('success');

    $report->refresh();
    expect($report->status)->toBe(WeeklyReport::STATUS_DRAFT);
    expect($report->submitted_at)->toBeNull();
    expect($report->submitted_by)->toBeNull();
});

it('prevents member from reopening their own submitted weekly report', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $report = WeeklyReport::factory()
        ->forCompany($company, $user)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'status' => WeeklyReport::STATUS_SUBMITTED,
        ]);

    $response = actingAs($user)->post(route('tenant.weekly-reports.reopen', [$company, $report]));

    $response->assertForbidden();
});

it('prevents reopening already draft weekly report', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create();

    $manager = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $report = WeeklyReport::factory()
        ->forCompany($company, $member)
        ->create([
            'work_year' => now()->isoFormat('GGGG'),
            'work_week' => now()->isoWeek(),
            'status' => WeeklyReport::STATUS_DRAFT,
        ]);

    // Policy returns false for draft reports, so reopen should be forbidden
    $response = actingAs($manager)->post(route('tenant.weekly-reports.reopen', [$company, $report]));

    $response->assertForbidden();
});

it('renders preview page for weekly report', function () {
    $user = createUserWithCompany(['role' => 'member'], ['onboarded_at' => now()]);
    $company = $user->company;

    $report = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'division_id' => $user->division_id,
        'department_id' => $user->department_id,
        'team_id' => $user->team_id,
        'work_year' => (int) now()->isoFormat('GGGG'),
        'work_week' => (int) now()->isoWeek(),
        'status' => WeeklyReport::STATUS_DRAFT,
        'summary' => '預覽測試摘要',
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
        'sort_order' => 0,
        'title' => '完成功能開發',
        'hours_spent' => 8.0,
        'planned_hours' => 6.0,
    ]);

    $response = actingAs($user)->get(route('tenant.weekly-reports.preview', [$company, $report]));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('weekly/preview')
            ->where('report.summary', '預覽測試摘要')
            ->where('report.currentWeek.0.title', '完成功能開發')
            ->where('report.currentWeek.0.hours_spent', 8)
            ->where('report.currentWeek.0.planned_hours', 6)
        );
});
