<?php

use App\Models\Company;
use App\Models\Division;
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

    $this->division = Division::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product Division',
        'slug' => 'product',
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'company_admin',
        'email' => 'admin@test.test',
    ]);

    $this->divisionLead = User::factory()->create([
        'company_id' => $this->company->id,
        'division_id' => $this->division->id,
        'role' => 'division_lead',
        'email' => 'lead@test.test',
    ]);

    $this->company->update(['current_user_count' => 2]);
});

it('公司管理者可以查看 division 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.divisions.invitation.show', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ])
    );

    $response->assertOk()
        ->assertJsonStructure([
            'invitation_token',
            'invitation_enabled',
            'invitation_url',
        ]);
});

it('division lead 可以查看自己的 division 邀請連結', function (): void {
    Sanctum::actingAs($this->divisionLead, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.divisions.invitation.show', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ])
    );

    $response->assertOk();
});

it('division lead 無法查看其他 division 的邀請連結', function (): void {
    $otherDivision = Division::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Other Division',
    ]);

    Sanctum::actingAs($this->divisionLead, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.divisions.invitation.show', [
            'company' => $this->company->slug,
            'division' => $otherDivision->id,
        ])
    );

    $response->assertForbidden();
});

it('可以生成 division 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->postJson(
        route('api.v1.tenant.divisions.invitation.generate', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ])
    );

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'invitation_token',
            'invitation_url',
        ]);

    $this->division->refresh();
    expect($this->division->invitation_token)->not->toBeNull();
});

it('division lead 可以生成自己的 division 邀請連結', function (): void {
    Sanctum::actingAs($this->divisionLead, guard: 'web');

    $response = $this->postJson(
        route('api.v1.tenant.divisions.invitation.generate', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ])
    );

    $response->assertOk();
});

it('可以啟用 division 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $this->division->generateInvitationToken();

    $response = $this->patchJson(
        route('api.v1.tenant.divisions.invitation.toggle', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ]),
        ['enabled' => true]
    );

    $response->assertOk()
        ->assertJsonPath('invitation_enabled', true);

    $this->division->refresh();
    expect($this->division->invitation_enabled)->toBeTrue();
});

it('可以停用 division 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $this->division->generateInvitationToken();
    $this->division->enableInvitation();

    $response = $this->patchJson(
        route('api.v1.tenant.divisions.invitation.toggle', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ]),
        ['enabled' => false]
    );

    $response->assertOk()
        ->assertJsonPath('invitation_enabled', false);

    $this->division->refresh();
    expect($this->division->invitation_enabled)->toBeFalse();
});

it('啟用邀請連結時會自動生成 token', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->patchJson(
        route('api.v1.tenant.divisions.invitation.toggle', [
            'company' => $this->company->slug,
            'division' => $this->division->id,
        ]),
        ['enabled' => true]
    );

    $response->assertOk();

    $this->division->refresh();
    expect($this->division->invitation_token)->not->toBeNull();
    expect($this->division->invitation_enabled)->toBeTrue();
});
