<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 0,
        'timezone' => 'Asia/Taipei',
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'company_admin',
        'email' => 'admin@test.test',
    ]);

    $this->company->update(['current_user_count' => 1]);

    Sanctum::actingAs($this->admin, guard: 'web');
});

it('可以取得組織層級設定', function (): void {
    $settings = $this->company->settings()->firstOrCreate([]);
    $settings->update(['organization_levels' => ['department', 'team']]);

    $response = $this->getJson(
        route('api.v1.tenant.settings.organization-levels.show', ['company' => $this->company->slug])
    );

    $response->assertOk()
        ->assertJson([
            'organization_levels' => ['department', 'team'],
        ]);
});

it('可以更新組織層級設定', function (): void {
    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['division', 'department', 'team'],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('organization_levels', ['division', 'department', 'team']);

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $this->company->id,
        'organization_levels' => json_encode(['division', 'department', 'team']),
    ]);
});

it('預設組織層級為 department', function (): void {
    $response = $this->getJson(
        route('api.v1.tenant.settings.organization-levels.show', ['company' => $this->company->slug])
    );

    $response->assertOk()
        ->assertJsonPath('organization_levels', ['department']);
});

it('非公司管理者無法更新組織層級設定', function (): void {
    $member = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'member',
        'email' => 'member@test.test',
    ]);

    Sanctum::actingAs($member, guard: 'web');

    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['division'],
        ]
    );

    $response->assertForbidden();
});

it('無法移除已有資料的層級', function (): void {
    // 先設定包含 division 的層級
    $settings = $this->company->settings()->firstOrCreate([]);
    $settings->update(['organization_levels' => ['division', 'department', 'team']]);

    Division::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Division',
        'slug' => 'test-division',
    ]);

    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['department', 'team'],
        ]
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['organization_levels']);
});

it('無法移除已有部門資料的層級', function (): void {
    Department::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Department',
        'slug' => 'test-department',
    ]);

    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['division', 'team'],
        ]
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['organization_levels']);
});

it('無法移除已有小組資料的層級', function (): void {
    // 先設定包含 team 的層級
    $settings = $this->company->settings()->firstOrCreate([]);
    $settings->update(['organization_levels' => ['division', 'department', 'team']]);

    Team::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Team',
        'slug' => 'test-team',
    ]);

    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['division', 'department'],
        ]
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['organization_levels']);
});

it('帶 force_remove_levels 時可移除已有資料的層級並清空該層級資料', function (): void {
    $settings = $this->company->settings()->firstOrCreate([]);
    $settings->update(['organization_levels' => ['division', 'department', 'team']]);

    $division = Division::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Division',
        'slug' => 'test-division',
    ]);
    $department = Department::factory()->create([
        'company_id' => $this->company->id,
        'division_id' => $division->id,
        'name' => 'Test Department',
        'slug' => 'test-department',
    ]);
    $team = Team::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $department->id,
        'division_id' => $division->id,
        'name' => 'Test Team',
        'slug' => 'test-team',
    ]);

    $this->admin->update(['division_id' => $division->id, 'department_id' => $department->id, 'team_id' => $team->id]);

    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['department'],
            'force_remove_levels' => ['division', 'department', 'team'],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('organization_levels', ['department']);

    expect(Team::withoutGlobalScopes()->find($team->id)->deleted_at)->not->toBeNull();
    expect(Department::withoutGlobalScopes()->find($department->id)->deleted_at)->not->toBeNull();
    expect(Division::withoutGlobalScopes()->find($division->id)->deleted_at)->not->toBeNull();
    $this->admin->refresh();
    expect($this->admin->division_id)->toBeNull()
        ->and($this->admin->department_id)->toBeNull()
        ->and($this->admin->team_id)->toBeNull();
});

it('驗證組織層級陣列格式', function (): void {
    $response = $this->putJson(
        route('api.v1.tenant.settings.organization-levels.update', ['company' => $this->company->slug]),
        [
            'organization_levels' => ['invalid_level'],
        ]
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['organization_levels.0']);
});

it('可透過 web 路由 PATCH 更新組織層級設定', function (): void {
    $this->actingAs($this->admin);

    $response = $this->patch(
        route('tenant.settings.organization-levels', [$this->company]),
        [
            'organization_levels' => ['division', 'department', 'team'],
        ],
        [
            'Accept' => 'application/json',
            'Referer' => route('tenant.settings', [$this->company]),
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success', '組織層級設定已更新');

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $this->company->id,
        'organization_levels' => json_encode(['division', 'department', 'team']),
    ]);
});
