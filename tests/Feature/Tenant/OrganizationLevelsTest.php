<?php

use App\Models\Company;
use App\Models\CompanySetting;
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
