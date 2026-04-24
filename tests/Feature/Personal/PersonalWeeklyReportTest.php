<?php

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;

test('personal user can view weekly reports index', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('personal.weekly-reports'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('personal/weekly-reports/list'));
});

test('tenant user is redirected away from personal app by middleware', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $response = $this->actingAs($user)->get(route('personal.weekly-reports'));

    $response->assertRedirect(route('tenant.home', $company));
});

test('guest is redirected to login from personal app', function () {
    $response = $this->get(route('personal.weekly-reports'));

    $response->assertRedirect(route('login'));
});

test('personal user can create a draft weekly report with items', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->post(route('personal.weekly-reports.store'), [
        'work_year' => 2026,
        'work_week' => 17,
        'summary' => '個人專案進度',
        'current_week' => [
            [
                'title' => '週報通開發',
                'content' => '完成個人週報基礎',
                'hours_spent' => 8,
            ],
        ],
        'next_week' => [
            [
                'title' => '公開分享',
                'content' => '設計 handle 流程',
            ],
        ],
    ]);

    /** @var WeeklyReport $report */
    $report = WeeklyReport::query()
        ->whereNull('company_id')
        ->where('user_id', $user->id)
        ->firstOrFail();

    expect($report->company_id)->toBeNull();
    expect($report->division_id)->toBeNull();
    expect($report->department_id)->toBeNull();
    expect($report->team_id)->toBeNull();
    expect($report->work_year)->toBe(2026);
    expect($report->work_week)->toBe(17);
    expect($report->status)->toBe(WeeklyReport::STATUS_DRAFT);
    expect($report->items()->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)->count())->toBe(1);
    expect($report->items()->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)->count())->toBe(1);

    $response->assertRedirect(route('personal.weekly-reports.edit', $report));
});

test('personal report listing only shows own personal reports', function () {
    $alice = User::factory()->create(['company_id' => null]);
    $bob = User::factory()->create(['company_id' => null]);
    $company = Company::factory()->create();

    // Alice's personal reports
    WeeklyReport::create([
        'company_id' => null,
        'user_id' => $alice->id,
        'work_year' => 2026,
        'work_week' => 15,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    // Bob's personal reports
    WeeklyReport::create([
        'company_id' => null,
        'user_id' => $bob->id,
        'work_year' => 2026,
        'work_week' => 15,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    // Alice's old tenant report (different user context, but same id in future? No, alice has no company)
    WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $alice->id,
        'work_year' => 2026,
        'work_week' => 10,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($alice)->get(route('personal.weekly-reports'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('personal/weekly-reports/list')
        ->has('reports', 1)
        ->where('reports.0.workWeek', 15)
    );
});

test('personal user cannot edit another personal user report', function () {
    $alice = User::factory()->create(['company_id' => null]);
    $bob = User::factory()->create(['company_id' => null]);

    $bobReport = WeeklyReport::create([
        'company_id' => null,
        'user_id' => $bob->id,
        'work_year' => 2026,
        'work_week' => 17,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($alice)->get(route('personal.weekly-reports.edit', $bobReport));

    $response->assertForbidden();
});

test('personal user cannot access a tenant weekly report via personal edit route', function () {
    $alice = User::factory()->create(['company_id' => null]);
    $company = Company::factory()->create();
    $tenantUser = User::factory()->for($company)->create();

    $tenantReport = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $tenantUser->id,
        'work_year' => 2026,
        'work_week' => 17,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($alice)->get(route('personal.weekly-reports.edit', $tenantReport));

    $response->assertForbidden();
});

test('personal user can submit a draft report', function () {
    $user = User::factory()->create(['company_id' => null]);

    $report = WeeklyReport::create([
        'company_id' => null,
        'user_id' => $user->id,
        'work_year' => 2026,
        'work_week' => 17,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($user)->post(route('personal.weekly-reports.submit', $report));

    $response->assertRedirect(route('personal.weekly-reports.edit', $report));
    expect($report->fresh()->status)->toBe(WeeklyReport::STATUS_SUBMITTED);
    expect($report->fresh()->submitted_by)->toBe($user->id);
});

test('personal user can update existing report', function () {
    $user = User::factory()->create(['company_id' => null]);

    $report = WeeklyReport::create([
        'company_id' => null,
        'user_id' => $user->id,
        'work_year' => 2026,
        'work_week' => 17,
        'summary' => 'old summary',
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($user)->put(route('personal.weekly-reports.update', $report), [
        'work_year' => 2026,
        'work_week' => 17,
        'summary' => 'new summary',
        'current_week' => [
            ['title' => 'task A', 'hours_spent' => 2],
        ],
        'next_week' => [],
    ]);

    $response->assertRedirect(route('personal.weekly-reports.edit', $report));
    expect($report->fresh()->summary)->toBe('new summary');
    expect($report->items()->count())->toBe(1);
});

test('personal user can delete own report', function () {
    $user = User::factory()->create(['company_id' => null]);

    $report = WeeklyReport::create([
        'company_id' => null,
        'user_id' => $user->id,
        'work_year' => 2026,
        'work_week' => 17,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($user)->delete(route('personal.weekly-reports.destroy', $report));

    $response->assertRedirect(route('personal.weekly-reports'));
    expect(WeeklyReport::query()->find($report->id))->toBeNull();
});

test('dashboard redirects personal user to personal home', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('personal.home'));
});

test('personal home redirects to weekly reports list', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('personal.home'));

    $response->assertRedirect(route('personal.weekly-reports'));
});

test('creating a report on existing week redirects to edit', function () {
    $user = User::factory()->create(['company_id' => null]);

    $existing = WeeklyReport::create([
        'company_id' => null,
        'user_id' => $user->id,
        'work_year' => 2026,
        'work_week' => 17,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($user)->get(route('personal.weekly-reports.create', ['year' => 2026, 'week' => 17]));

    $response->assertRedirect(route('personal.weekly-reports.edit', $existing));
});
