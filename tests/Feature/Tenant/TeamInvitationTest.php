<?php

use App\Models\Company;
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

    $this->team = Team::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Backend Team',
        'slug' => 'backend',
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'company_admin',
        'email' => 'admin@test.test',
    ]);

    $this->teamLead = User::factory()->create([
        'company_id' => $this->company->id,
        'team_id' => $this->team->id,
        'role' => 'team_lead',
        'email' => 'lead@test.test',
    ]);

    $this->company->update(['current_user_count' => 2]);
});

it('公司管理者可以查看 team 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.teams.invitation.show', [
            'company' => $this->company->slug,
            'team' => $this->team->id,
        ])
    );

    $response->assertOk()
        ->assertJsonStructure([
            'invitation_token',
            'invitation_enabled',
            'invitation_url',
        ]);
});

it('team lead 可以查看自己的 team 邀請連結', function (): void {
    Sanctum::actingAs($this->teamLead, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.teams.invitation.show', [
            'company' => $this->company->slug,
            'team' => $this->team->id,
        ])
    );

    $response->assertOk();
});

it('team lead 無法查看其他 team 的邀請連結', function (): void {
    $otherTeam = Team::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Frontend Team',
    ]);

    Sanctum::actingAs($this->teamLead, guard: 'web');

    $response = $this->getJson(
        route('api.v1.tenant.teams.invitation.show', [
            'company' => $this->company->slug,
            'team' => $otherTeam->id,
        ])
    );

    $response->assertForbidden();
});

it('可以生成 team 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->postJson(
        route('api.v1.tenant.teams.invitation.generate', [
            'company' => $this->company->slug,
            'team' => $this->team->id,
        ])
    );

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'invitation_token',
            'invitation_url',
        ]);

    $this->team->refresh();
    expect($this->team->invitation_token)->not->toBeNull();
});

it('可以啟用和停用 team 邀請連結', function (): void {
    Sanctum::actingAs($this->admin, guard: 'web');

    $this->team->generateInvitationToken();

    $response = $this->patchJson(
        route('api.v1.tenant.teams.invitation.toggle', [
            'company' => $this->company->slug,
            'team' => $this->team->id,
        ]),
        ['enabled' => true]
    );

    $response->assertOk()
        ->assertJsonPath('invitation_enabled', true);

    $this->team->refresh();
    expect($this->team->invitation_enabled)->toBeTrue();

    $response = $this->patchJson(
        route('api.v1.tenant.teams.invitation.toggle', [
            'company' => $this->company->slug,
            'team' => $this->team->id,
        ]),
        ['enabled' => false]
    );

    $response->assertOk()
        ->assertJsonPath('invitation_enabled', false);

    $this->team->refresh();
    expect($this->team->invitation_enabled)->toBeFalse();
});
