<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\Schema;

it('allows weekly_reports.company_id to be null', function () {
    $columns = collect(Schema::getColumns('weekly_reports'))->keyBy('name');

    expect($columns->get('company_id'))->not->toBeNull()
        ->and($columns->get('company_id')['nullable'])->toBeTrue();
});

it('detaches weekly_reports when their company is force deleted', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $report = WeeklyReport::factory()
        ->for($company)
        ->for($user)
        ->create();

    $company->forceDelete();

    expect($report->fresh())
        ->not->toBeNull()
        ->company_id->toBeNull();
});

it('soft deletes company_settings', function () {
    expect(Schema::hasColumn('company_settings', 'deleted_at'))->toBeTrue();

    $company = Company::factory()->create();
    $setting = CompanySetting::query()->create([
        'company_id' => $company->id,
        'welcome_page' => null,
        'login_ip_whitelist' => null,
        'notification_preferences' => null,
        'default_weekly_report_modules' => null,
        'organization_levels' => ['department'],
    ]);

    $setting->delete();

    expect(CompanySetting::query()->find($setting->id))->toBeNull()
        ->and(CompanySetting::query()->withTrashed()->find($setting->id))->not->toBeNull();
});

it('adds invitation_revoked_at column to users', function () {
    expect(Schema::hasColumn('users', 'invitation_revoked_at'))->toBeTrue();

    $user = User::factory()->create(['invitation_revoked_at' => now()]);

    expect($user->fresh()->invitation_revoked_at)->not->toBeNull();
});
