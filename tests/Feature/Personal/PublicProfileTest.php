<?php

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;

function makePublicReport(User $user, int $year, int $week): WeeklyReport
{
    return WeeklyReport::create([
        'company_id' => null,
        'user_id' => $user->id,
        'work_year' => $year,
        'work_week' => $week,
        'status' => WeeklyReport::STATUS_SUBMITTED,
        'is_public' => true,
        'published_at' => now(),
        'summary' => "week {$year}/{$week}",
    ]);
}

test('public profile page shows public submitted reports ordered desc', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);

    makePublicReport($alice, 2026, 15);
    makePublicReport($alice, 2026, 17);
    makePublicReport($alice, 2026, 10);

    $response = $this->get('/u/alice');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/profile')
        ->where('profile.handle', 'alice')
        ->has('reports.data', 3)
        ->where('reports.data.0.workWeek', 17)
        ->where('reports.data.1.workWeek', 15)
        ->where('reports.data.2.workWeek', 10)
    );
});

test('public profile hides private or draft reports', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);

    // Private submitted
    WeeklyReport::create([
        'company_id' => null, 'user_id' => $alice->id,
        'work_year' => 2026, 'work_week' => 14,
        'status' => WeeklyReport::STATUS_SUBMITTED, 'is_public' => false,
    ]);

    // Draft but is_public=true (defensive: never leaks)
    WeeklyReport::create([
        'company_id' => null, 'user_id' => $alice->id,
        'work_year' => 2026, 'work_week' => 13,
        'status' => WeeklyReport::STATUS_DRAFT, 'is_public' => true,
    ]);

    // Public submitted
    makePublicReport($alice, 2026, 17);

    $this->get('/u/alice')
        ->assertInertia(fn ($page) => $page->has('reports.data', 1)->where('reports.data.0.workWeek', 17));
});

test('public profile excludes tenant reports even if same user id', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    $company = Company::factory()->create();

    // Tenant report with is_public=true shouldn't leak
    WeeklyReport::create([
        'company_id' => $company->id, 'user_id' => $alice->id,
        'work_year' => 2026, 'work_week' => 5,
        'status' => WeeklyReport::STATUS_SUBMITTED, 'is_public' => true,
    ]);

    makePublicReport($alice, 2026, 17);

    $this->get('/u/alice')
        ->assertInertia(fn ($page) => $page->has('reports.data', 1)->where('reports.data.0.workWeek', 17));
});

test('unknown handle returns 404', function () {
    $this->get('/u/does-not-exist')->assertNotFound();
});

test('reserved handle returns 404 without user lookup', function () {
    User::factory()->create(['company_id' => null, 'handle' => 'admin']);

    $this->get('/u/admin')->assertNotFound();
});

test('single report page renders for a public report', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    makePublicReport($alice, 2026, 17);

    $this->get('/u/alice/2026/17')->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/report')
            ->where('profile.handle', 'alice')
            ->where('report.workYear', 2026)
            ->where('report.workWeek', 17));
});

test('single report page returns 404 for private/draft/tenant report', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);

    // Private
    WeeklyReport::create([
        'company_id' => null, 'user_id' => $alice->id,
        'work_year' => 2026, 'work_week' => 17,
        'status' => WeeklyReport::STATUS_SUBMITTED, 'is_public' => false,
    ]);

    $this->get('/u/alice/2026/17')->assertNotFound();
});

test('single report page returns 404 for wrong year/week', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    makePublicReport($alice, 2026, 17);

    $this->get('/u/alice/2026/18')->assertNotFound();
});

test('guest can access public profile without redirect', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    makePublicReport($alice, 2026, 17);

    $response = $this->get('/u/alice');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('public/profile'));
});
