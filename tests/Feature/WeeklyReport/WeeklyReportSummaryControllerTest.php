<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createCompanyWithSettings(array $companyAttributes = []): Company
{
    $company = Company::factory()->create(array_merge([
        'onboarded_at' => now(),
    ], $companyAttributes));
    CompanySetting::factory()->for($company)->create();

    return $company;
}

function createReportWithItems(Company $company, User $user, array $reportAttributes = [], array $items = []): WeeklyReport
{
    $report = WeeklyReport::create(array_merge([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'division_id' => $user->division_id,
        'department_id' => $user->department_id,
        'team_id' => $user->team_id,
        'work_year' => now()->isoWeekYear(),
        'work_week' => now()->isoWeek(),
        'status' => WeeklyReport::STATUS_SUBMITTED,
        'summary' => 'Test summary',
    ], $reportAttributes));

    foreach ($items as $item) {
        WeeklyReportItem::create(array_merge([
            'weekly_report_id' => $report->id,
            'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
            'sort_order' => 0,
        ], $item));
    }

    return $report;
}

it('returns summary for company admin', function () {
    $company = createCompanyWithSettings();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);
    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    createReportWithItems($company, $member, [], [
        ['title' => 'Task 1', 'hours_spent' => 8, 'is_billable' => true],
        ['title' => 'Task 2', 'hours_spent' => 4, 'is_billable' => false],
    ]);

    $response = actingAs($admin)->getJson(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'year' => now()->isoWeekYear(),
            'week' => now()->isoWeek(),
        ])
    );

    $response->assertOk()
        ->assertJsonPath('data.level', 'company')
        ->assertJsonPath('data.total_hours', 12)
        ->assertJsonPath('data.billable_hours', 8)
        ->assertJsonPath('data.report_count', 1)
        ->assertJsonPath('data.submitted_count', 1)
        ->assertJsonPath('data.member_count', 1);
});

it('returns summary filtered by year', function () {
    $company = createCompanyWithSettings();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    createReportWithItems($company, $admin, ['work_year' => 2025, 'work_week' => 1], [
        ['title' => 'Task 2025', 'hours_spent' => 10],
    ]);

    createReportWithItems($company, $admin, ['work_year' => 2026, 'work_week' => 1], [
        ['title' => 'Task 2026', 'hours_spent' => 20],
    ]);

    $response = actingAs($admin)->getJson(
        route('tenant.weekly-reports.summary', ['company' => $company, 'year' => 2026])
    );

    $response->assertOk()
        ->assertJsonPath('data.total_hours', 20)
        ->assertJsonPath('data.report_count', 1);
});

it('returns summary filtered by week', function () {
    $company = createCompanyWithSettings();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    createReportWithItems($company, $admin, ['work_year' => 2026, 'work_week' => 5], [
        ['title' => 'Week 5', 'hours_spent' => 15],
    ]);

    createReportWithItems($company, $admin, ['work_year' => 2026, 'work_week' => 6], [
        ['title' => 'Week 6', 'hours_spent' => 25],
    ]);

    $response = actingAs($admin)->getJson(
        route('tenant.weekly-reports.summary', ['company' => $company, 'year' => 2026, 'week' => 5])
    );

    $response->assertOk()
        ->assertJsonPath('data.total_hours', 15)
        ->assertJsonPath('data.report_count', 1);
});

it('returns member summaries with report details', function () {
    $company = createCompanyWithSettings();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);
    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
        'name' => 'Test Member',
    ]);

    createReportWithItems($company, $member, [], [
        ['title' => 'Task A', 'hours_spent' => 8, 'is_billable' => true],
    ]);

    $response = actingAs($admin)->getJson(
        route('tenant.weekly-reports.summary', ['company' => $company])
    );

    $response->assertOk()
        ->assertJsonPath('data.members.0.user_name', 'Test Member')
        ->assertJsonPath('data.members.0.total_hours', 8)
        ->assertJsonPath('data.members.0.report_count', 1)
        ->assertJsonStructure([
            'data' => [
                'members' => [
                    '*' => [
                        'user_id',
                        'user_name',
                        'user_email',
                        'total_hours',
                        'billable_hours',
                        'report_count',
                        'reports',
                    ],
                ],
            ],
        ]);
});

it('denies access for regular member', function () {
    $company = createCompanyWithSettings();
    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $response = actingAs($member)->getJson(
        route('tenant.weekly-reports.summary', ['company' => $company])
    );

    $response->assertForbidden();
});

it('allows team lead to view summary for their team', function () {
    $company = createCompanyWithSettings();
    $team = \App\Models\Team::factory()->for($company)->create();

    $teamLead = User::factory()->create([
        'company_id' => $company->id,
        'team_id' => $team->id,
        'role' => 'team_lead',
    ]);
    $teamMember = User::factory()->create([
        'company_id' => $company->id,
        'team_id' => $team->id,
        'role' => 'member',
    ]);

    createReportWithItems($company, $teamMember, ['team_id' => $team->id], [
        ['title' => 'Team Task', 'hours_spent' => 6],
    ]);

    $response = actingAs($teamLead)->getJson(
        route('tenant.weekly-reports.summary', ['company' => $company])
    );

    $response->assertOk()
        ->assertJsonPath('data.report_count', 1);
});

it('only counts current week items in hours calculation', function () {
    $company = createCompanyWithSettings();
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    $report = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'work_year' => now()->isoWeekYear(),
        'work_week' => now()->isoWeek(),
        'status' => WeeklyReport::STATUS_SUBMITTED,
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
        'sort_order' => 0,
        'title' => 'Current',
        'hours_spent' => 10,
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'type' => WeeklyReportItem::TYPE_NEXT_WEEK,
        'sort_order' => 0,
        'title' => 'Next',
        'planned_hours' => 15,
    ]);

    $response = actingAs($admin)->getJson(
        route('tenant.weekly-reports.summary', ['company' => $company])
    );

    $response->assertOk()
        ->assertJsonPath('data.total_hours', 10);
});

it('requires authentication', function () {
    $company = createCompanyWithSettings();

    $response = $this->getJson(route('tenant.weekly-reports.summary', ['company' => $company]));

    $response->assertUnauthorized();
});
