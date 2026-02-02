<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Notifications\WeeklyReportReminder;
use App\Notifications\WeeklyReportSubmitted;
use App\Notifications\WeeklySummaryDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

function createCompanyWithAdmin(): array
{
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create();

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    return [$company, $admin];
}

it('sends weekly report reminder notification', function () {
    [$company, $admin] = createCompanyWithAdmin();

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $workYear = now()->isoWeekYear();
    $workWeek = now()->isoWeek();

    $member->notify(new WeeklyReportReminder($company, $workYear, $workWeek));

    Notification::assertSentTo($member, WeeklyReportReminder::class);
});

it('sends weekly report submitted notification', function () {
    [$company, $admin] = createCompanyWithAdmin();

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $report = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $member->id,
        'work_year' => now()->isoWeekYear(),
        'work_week' => now()->isoWeek(),
        'status' => WeeklyReport::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);

    $admin->notify(new WeeklyReportSubmitted($company, $report, $member));

    Notification::assertSentTo($admin, WeeklyReportSubmitted::class);
});

it('sends weekly summary digest notification', function () {
    [$company, $admin] = createCompanyWithAdmin();

    $summary = [
        'total_hours' => 40,
        'billable_hours' => 32,
        'report_count' => 5,
        'submitted_count' => 4,
        'draft_count' => 1,
        'member_count' => 5,
    ];

    $admin->notify(new WeeklySummaryDigest($company, 2026, 5, $summary));

    Notification::assertSentTo($admin, WeeklySummaryDigest::class, function ($notification) {
        return $notification->workYear === 2026
            && $notification->workWeek === 5
            && $notification->summary['total_hours'] === 40;
    });
});

it('reminder notification contains correct data', function () {
    [$company] = createCompanyWithAdmin();

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $notification = new WeeklyReportReminder($company, 2026, 5);

    expect($notification->company->id)->toBe($company->id);
    expect($notification->workYear)->toBe(2026);
    expect($notification->workWeek)->toBe(5);
    expect($notification->via($member))->toBe(['mail']);
});

it('digest notification contains summary data', function () {
    [$company, $admin] = createCompanyWithAdmin();

    $summary = [
        'total_hours' => 80,
        'billable_hours' => 64,
        'report_count' => 10,
        'submitted_count' => 8,
        'draft_count' => 2,
        'member_count' => 10,
    ];

    $notification = new WeeklySummaryDigest($company, 2026, 5, $summary);

    expect($notification->toArray($admin))->toMatchArray([
        'company_id' => $company->id,
        'work_year' => 2026,
        'work_week' => 5,
        'summary' => $summary,
    ]);
});

it('send reminders command sends notifications to users without reports', function () {
    [$company] = createCompanyWithAdmin();

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    $this->artisan('weekly-report:send-reminders')
        ->assertExitCode(0);

    Notification::assertSentTo($member, WeeklyReportReminder::class);
});

it('send reminders command skips users with submitted reports', function () {
    [$company] = createCompanyWithAdmin();

    $member = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $member->id,
        'work_year' => now()->isoWeekYear(),
        'work_week' => now()->isoWeek(),
        'status' => WeeklyReport::STATUS_SUBMITTED,
    ]);

    $this->artisan('weekly-report:send-reminders')
        ->assertExitCode(0);

    Notification::assertNotSentTo($member, WeeklyReportReminder::class);
});
