<?php

use App\Models\Company;
use App\Models\Department;
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

    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Engineering',
        'slug' => 'engineering',
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'company_admin',
        'email' => 'admin@test.test',
    ]);

    $this->departmentManager = User::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'role' => 'department_manager',
        'email' => 'manager@test.test',
    ]);

    $this->company->update(['current_user_count' => 2]);
});

it('公司管理者可以查看 department 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.departments.invitation.show', [
            'company' => $this->company->slug,
            'department' => $this->department->id,
        ])
    );

    $response->assertOk()
        ->assertJsonStructure([
            'invitation_token',
            'invitation_enabled',
            'invitation_url',
        ]);
});

it('department manager 可以查看自己的 department 邀請連結', function (): void {
    Sanctum::actingAs($this->departmentManager, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.departments.invitation.show', [
            'company' => $this->company->slug,
            'department' => $this->department->id,
        ])
    );

    $response->assertOk();
});

it('department manager 無法查看其他 department 的邀請連結', function (): void {
    $otherDepartment = Department::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Sales',
    ]);

    Sanctum::actingAs($this->departmentManager, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.departments.invitation.show', [
            'company' => $this->company->slug,
            'department' => $otherDepartment->id,
        ])
    );

    $response->assertForbidden();
});

it('可以生成 department 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->postJson(
        route('api.v1.tenant.departments.invitation.generate', [
            'company' => $this->company->slug,
            'department' => $this->department->id,
        ])
    );

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'invitation_token',
            'invitation_url',
        ]);

    $this->department->refresh();
    expect($this->department->invitation_token)->not->toBeNull();
});

it('可以啟用和停用 department 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $this->department->generateInvitationToken();

    $response = $this->patchJson(
        route('api.v1.tenant.departments.invitation.toggle', [
            'company' => $this->company->slug,
            'department' => $this->department->id,
        ]),
        ['enabled' => true]
    );

    $response->assertOk()
        ->assertJsonPath('invitation_enabled', true);

    $this->department->refresh();
    expect($this->department->invitation_enabled)->toBeTrue();

    $response = $this->patchJson(
        route('api.v1.tenant.departments.invitation.toggle', [
            'company' => $this->company->slug,
            'department' => $this->department->id,
        ]),
        ['enabled' => false]
    );

    $response->assertOk()
        ->assertJsonPath('invitation_enabled', false);

    $this->department->refresh();
    expect($this->department->invitation_enabled)->toBeFalse();
});
