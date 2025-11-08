<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::create([
        'name' => 'Alpha Corp',
        'slug' => 'alpha-corp',
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 0,
        'timezone' => 'Asia/Taipei',
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'company_admin',
        'email' => 'admin@alpha.test',
    ]);

    $this->company->update(['current_user_count' => 1]);

    Sanctum::actingAs($this->admin, guard: 'web');
});

it('阻止同一租戶使用重複的電子郵件邀請', function (): void {
    User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'member@alpha.test',
    ]);

    $response = $this->postJson(
        route('api.v1.tenant.members.invite', ['company' => $this->company->slug]),
        [
            'email' => 'member@alpha.test',
            'name' => 'Duplicate Member',
            'role' => 'member',
        ]
    );

    $response->assertStatus(422);
});

it('允許不同租戶使用相同電子郵件邀請', function (): void {
    $betaCompany = Company::create([
        'name' => 'Beta Corp',
        'slug' => 'beta-corp',
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 0,
        'timezone' => 'Asia/Taipei',
    ]);

    User::factory()->create([
        'company_id' => $betaCompany->id,
        'email' => 'shared@tenant.test',
    ]);

    $response = $this->postJson(
        route('api.v1.tenant.members.invite', ['company' => $this->company->slug]),
        [
            'email' => 'shared@tenant.test',
            'name' => 'Shared Member',
            'role' => 'member',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('member.email', 'shared@tenant.test');

    $this->assertDatabaseHas('users', [
        'company_id' => $this->company->id,
        'email' => 'shared@tenant.test',
    ]);
});
