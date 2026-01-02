<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
});

it('可以透過 division 邀請連結註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product Division',
    ]);

    $token = $division->generateInvitationToken();
    $division->enableInvitation();

    $response = $this->get(
        route('tenant.register-by-invitation', [
            'company' => $this->company->slug,
            'token' => $token,
            'type' => 'division',
        ])
    );

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenant/register-by-invitation')
            ->where('company.name', 'Test Corp')
            ->where('organization.name', 'Product Division')
            ->where('organization.type', 'division')
        );
});

it('可以透過 department 邀請連結註冊', function (): void {
    $department = Department::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Engineering',
    ]);

    $token = $department->generateInvitationToken();
    $department->enableInvitation();

    $response = $this->get(
        route('tenant.register-by-invitation', [
            'company' => $this->company->slug,
            'token' => $token,
            'type' => 'department',
        ])
    );

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenant/register-by-invitation')
            ->where('organization.name', 'Engineering')
            ->where('organization.type', 'department')
        );
});

it('可以透過 team 邀請連結註冊', function (): void {
    $team = Team::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Backend Team',
    ]);

    $token = $team->generateInvitationToken();
    $team->enableInvitation();

    $response = $this->get(
        route('tenant.register-by-invitation', [
            'company' => $this->company->slug,
            'token' => $token,
            'type' => 'team',
        ])
    );

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tenant/register-by-invitation')
            ->where('organization.name', 'Backend Team')
            ->where('organization.type', 'team')
        );
});

it('無法使用停用的邀請連結註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $token = $division->generateInvitationToken();
    $division->disableInvitation();

    $response = $this->get(
        route('tenant.register-by-invitation', [
            'company' => $this->company->slug,
            'token' => $token,
            'type' => 'division',
        ])
    );

    $response->assertNotFound();
});

it('無法使用無效的邀請連結註冊', function (): void {
    $response = $this->get(
        route('tenant.register-by-invitation', [
            'company' => $this->company->slug,
            'token' => 'invalid-token',
            'type' => 'division',
        ])
    );

    $response->assertNotFound();
});

it('可以透過 division 邀請連結完成註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product Division',
    ]);

    $token = $division->generateInvitationToken();
    $division->enableInvitation();

    $response = $this->post(
        route('tenant.register-by-invitation.store', ['company' => $this->company->slug]),
        [
            'token' => $token,
            'type' => 'division',
            'name' => 'New User',
            'email' => 'newuser@test.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]
    );

    $response->assertRedirect(route('tenant.weekly-reports', $this->company));

    $this->assertDatabaseHas('users', [
        'company_id' => $this->company->id,
        'division_id' => $division->id,
        'department_id' => null,
        'team_id' => null,
        'email' => 'newuser@test.test',
        'name' => 'New User',
        'role' => 'member',
        'registered_via' => 'invitation_link',
    ]);

    $this->assertAuthenticated();
});

it('可以透過 department 邀請連結完成註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $department = Department::factory()->create([
        'company_id' => $this->company->id,
        'division_id' => $division->id,
        'name' => 'Engineering',
    ]);

    $token = $department->generateInvitationToken();
    $department->enableInvitation();

    $response = $this->post(
        route('tenant.register-by-invitation.store', ['company' => $this->company->slug]),
        [
            'token' => $token,
            'type' => 'department',
            'name' => 'New User',
            'email' => 'newuser@test.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]
    );

    $response->assertRedirect(route('tenant.weekly-reports', $this->company));

    $this->assertDatabaseHas('users', [
        'company_id' => $this->company->id,
        'division_id' => $division->id,
        'department_id' => $department->id,
        'team_id' => null,
        'email' => 'newuser@test.test',
        'role' => 'member',
    ]);
});

it('可以透過 team 邀請連結完成註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $department = Department::factory()->create([
        'company_id' => $this->company->id,
        'division_id' => $division->id,
    ]);

    $team = Team::factory()->create([
        'company_id' => $this->company->id,
        'division_id' => $division->id,
        'department_id' => $department->id,
        'name' => 'Backend Team',
    ]);

    $token = $team->generateInvitationToken();
    $team->enableInvitation();

    $response = $this->post(
        route('tenant.register-by-invitation.store', ['company' => $this->company->slug]),
        [
            'token' => $token,
            'type' => 'team',
            'name' => 'New User',
            'email' => 'newuser@test.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]
    );

    $response->assertRedirect(route('tenant.weekly-reports', $this->company));

    $this->assertDatabaseHas('users', [
        'company_id' => $this->company->id,
        'division_id' => $division->id,
        'department_id' => $department->id,
        'team_id' => $team->id,
        'email' => 'newuser@test.test',
        'role' => 'member',
    ]);
});

it('無法使用已存在的 email 註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
    ]);

    User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'existing@test.test',
    ]);

    $token = $division->generateInvitationToken();
    $division->enableInvitation();

    $response = $this->post(
        route('tenant.register-by-invitation.store', ['company' => $this->company->slug]),
        [
            'token' => $token,
            'type' => 'division',
            'name' => 'New User',
            'email' => 'existing@test.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]
    );

    $response->assertSessionHasErrors(['email']);
});

it('無法在達到用戶上限時註冊', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->company->update([
        'user_limit' => 1,
        'current_user_count' => 1,
    ]);

    $token = $division->generateInvitationToken();
    $division->enableInvitation();

    $response = $this->post(
        route('tenant.register-by-invitation.store', ['company' => $this->company->slug]),
        [
            'token' => $token,
            'type' => 'division',
            'name' => 'New User',
            'email' => 'newuser@test.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]
    );

    $response->assertSessionHasErrors(['email']);
});

it('註冊後會增加公司用戶計數', function (): void {
    $division = Division::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $token = $division->generateInvitationToken();
    $division->enableInvitation();

    $initialCount = $this->company->fresh()->current_user_count;

    $this->post(
        route('tenant.register-by-invitation.store', ['company' => $this->company->slug]),
        [
            'token' => $token,
            'type' => 'division',
            'name' => 'New User',
            'email' => 'newuser@test.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]
    );

    expect($this->company->fresh()->current_user_count)->toBe($initialCount + 1);
});
