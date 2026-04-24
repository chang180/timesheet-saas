<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use App\Models\WeeklyReport;

test('sole company admin can dissolve the company', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $response = $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company));

    $response->assertRedirect(route('personal.home'));

    $fresh = $admin->fresh();
    expect($fresh->company_id)->toBeNull();
    expect($fresh->isPersonal())->toBeTrue();
    expect($fresh->registered_via)->toBe('personal-after-dissolve');
    expect(Company::withTrashed()->find($company->id)->status)->toBe('dissolved');
});

test('dissolve converts sole member weekly reports to personal', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $report = WeeklyReport::create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'work_year' => 2026,
        'work_week' => 10,
        'status' => WeeklyReport::STATUS_DRAFT,
    ]);

    $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertRedirect();

    $freshReport = WeeklyReport::withoutGlobalScopes()->find($report->id);
    expect($freshReport->company_id)->toBeNull();
    expect($freshReport->deleted_at)->toBeNull();
});

test('dissolve soft-deletes org structures', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $division = Division::factory()->for($company)->create();
    $department = Department::factory()->for($company)->for($division)->create();
    $team = Team::factory()->for($company)->for($department)->for($division)->create();

    $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertRedirect();

    expect(Division::withoutGlobalScopes()->find($division->id)->deleted_at)->not->toBeNull();
    expect(Department::withoutGlobalScopes()->find($department->id)->deleted_at)->not->toBeNull();
    expect(Team::withoutGlobalScopes()->find($team->id)->deleted_at)->not->toBeNull();
});

test('dissolve writes audit log', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertRedirect();

    expect(AuditLog::query()
        ->where('event', 'company.dissolved')
        ->where('auditable_id', $company->id)
        ->exists())->toBeTrue();
});

test('cannot dissolve company that still has other members', function () {
    $company = Company::factory()->create(['current_user_count' => 2]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    User::factory()->for($company)->create(['role' => 'member']);

    $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertStatus(422);

    expect($admin->fresh()->company_id)->toBe($company->id);
    expect(Company::find($company->id))->not->toBeNull();
});

test('non admin cannot dissolve company', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $member = User::factory()->for($company)->create(['role' => 'member']);

    $this->actingAs($member)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertForbidden();

    expect(Company::find($company->id))->not->toBeNull();
});

test('dissolved user becomes personal and can access personal app', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertRedirect(route('personal.home'));

    $fresh = $admin->fresh();

    $this->actingAs($fresh)
        ->get(route('personal.weekly-reports'))
        ->assertOk();
});

test('company settings are soft-deleted on dissolve', function () {
    $company = Company::factory()->create(['current_user_count' => 1]);
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    CompanySetting::factory()->for($company)->create();

    $this->actingAs($admin)
        ->delete(route('tenant.settings.dissolve', $company))
        ->assertRedirect();

    expect(CompanySetting::withTrashed()->where('company_id', $company->id)->whereNotNull('deleted_at')->exists())->toBeTrue();
});
