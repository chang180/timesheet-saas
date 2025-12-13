<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

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
            ->where('prefill.currentWeek.0.hours_spent', 5)
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

