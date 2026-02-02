<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function setupCompanyWithReports(): array
{
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
    ]);

    $report = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'work_year' => 2026,
        'work_week' => 5,
        'status' => WeeklyReport::STATUS_SUBMITTED,
        'summary' => 'Test summary',
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
        'sort_order' => 0,
        'title' => 'Task A',
        'content' => 'Task A content',
        'hours_spent' => 8,
        'is_billable' => true,
        'issue_reference' => 'JIRA-123',
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'type' => WeeklyReportItem::TYPE_NEXT_WEEK,
        'sort_order' => 0,
        'title' => 'Plan B',
        'planned_hours' => 4,
    ]);

    return [$company, $admin, $report];
}

it('exports weekly reports as CSV', function () {
    [$company, $admin] = setupCompanyWithReports();

    $response = actingAs($admin)->get(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'year' => 2026,
            'week' => 5,
            'export' => 'csv',
        ])
    );

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('成員')
        ->toContain('信箱')
        ->toContain('實際工時')
        ->toContain('Test Admin')
        ->toContain('admin@test.com')
        ->toContain('Task A')
        ->toContain('JIRA-123');
});

it('exports weekly reports as XLSX', function () {
    [$company, $admin] = setupCompanyWithReports();

    $response = actingAs($admin)->get(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'year' => 2026,
            'week' => 5,
            'export' => 'xlsx',
        ])
    );

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('generates correct filename format', function () {
    [$company, $admin] = setupCompanyWithReports();

    $response = actingAs($admin)->get(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'year' => 2026,
            'week' => 5,
            'export' => 'csv',
        ])
    );

    $response->assertOk();

    $contentDisposition = $response->headers->get('content-disposition');
    expect($contentDisposition)->toContain($company->slug)
        ->toContain('company')
        ->toContain('202605')
        ->toContain('.csv');
});

it('denies export for unauthorized users', function () {
    [$company] = setupCompanyWithReports();

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $response = actingAs($member)->get(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'export' => 'csv',
        ])
    );

    $response->assertForbidden();
});

it('includes both current week and next week items in export', function () {
    [$company, $admin] = setupCompanyWithReports();

    $response = actingAs($admin)->get(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'year' => 2026,
            'week' => 5,
            'export' => 'csv',
        ])
    );

    $content = $response->streamedContent();
    expect($content)->toContain('Task A')
        ->toContain('本週工作')
        ->toContain('Plan B')
        ->toContain('下週計畫');
});

it('creates audit log on export', function () {
    [$company, $admin] = setupCompanyWithReports();

    actingAs($admin)->get(
        route('tenant.weekly-reports.summary', [
            'company' => $company,
            'export' => 'csv',
        ])
    );

    $this->assertDatabaseHas('audit_logs', [
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'event' => 'exported',
    ]);
});
