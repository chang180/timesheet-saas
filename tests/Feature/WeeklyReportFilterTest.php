<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $company = Company::factory()->create([
        'onboarded_at' => now(),
    ]);
    CompanySetting::factory()->for($company)->create();

    $this->user = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);
    $this->company = $company;
});

test('weekly report list shows filters props', function () {
    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', $this->company));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->has('filters')
        ->where('filters.year', 'all')
        ->where('filters.status', 'all')
        ->has('availableYears')
    );
});

test('can filter weekly reports by year', function () {
    // Create reports in different years
    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2025,
            'work_week' => 1,
        ]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2026,
            'work_week' => 1,
        ]);

    // Filter by 2025
    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', [$this->company, 'filter_year' => '2025']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->where('filters.year', '2025')
        ->has('reports', 1)
        ->where('reports.0.workYear', 2025)
    );
});

test('can filter weekly reports by status', function () {
    // Create reports with different statuses
    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2026,
            'work_week' => 1,
            'status' => 'draft',
        ]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2026,
            'work_week' => 2,
            'status' => 'submitted',
        ]);

    // Filter by draft
    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', [$this->company, 'filter_status' => 'draft']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->where('filters.status', 'draft')
        ->has('reports', 1)
        ->where('reports.0.status', 'draft')
    );
});

test('can filter weekly reports by both year and status', function () {
    // Create reports with different years and statuses
    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2025,
            'work_week' => 1,
            'status' => 'draft',
        ]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2025,
            'work_week' => 2,
            'status' => 'submitted',
        ]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2026,
            'work_week' => 1,
            'status' => 'draft',
        ]);

    // Filter by 2025 and submitted
    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', [
            $this->company,
            'filter_year' => '2025',
            'filter_status' => 'submitted',
        ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->where('filters.year', '2025')
        ->where('filters.status', 'submitted')
        ->has('reports', 1)
        ->where('reports.0.workYear', 2025)
        ->where('reports.0.status', 'submitted')
    );
});

test('filter with all returns all reports', function () {
    // Create reports with unique year/week combinations
    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create(['work_year' => 2026, 'work_week' => 1]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create(['work_year' => 2026, 'work_week' => 2]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create(['work_year' => 2026, 'work_week' => 3]);

    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', [
            $this->company,
            'filter_year' => 'all',
            'filter_status' => 'all',
        ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->has('reports', 3)
    );
});

test('available years includes current year even without reports', function () {
    $currentYear = (int) now()->isoFormat('GGGG');

    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', $this->company));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->where('availableYears', fn ($years) => in_array($currentYear, is_array($years) ? $years : collect($years)->all()))
    );
});

test('available years includes years from existing reports', function () {
    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2024,
            'work_week' => 1,
        ]);

    WeeklyReport::factory()
        ->forCompany($this->company, $this->user)
        ->create([
            'work_year' => 2025,
            'work_week' => 1,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('tenant.weekly-reports', $this->company));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('weekly/list')
        ->where('availableYears', function ($years) {
            $yearsArray = is_array($years) ? $years : collect($years)->all();

            return in_array(2024, $yearsArray) && in_array(2025, $yearsArray);
        })
    );
});
