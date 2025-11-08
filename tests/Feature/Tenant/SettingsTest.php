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

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Test Company',
        'slug' => 'test-company',
        'status' => 'active',
        'user_limit' => 5,
        'current_user_count' => 0,
        'timezone' => 'Asia/Taipei',
    ]);

    CompanySetting::create([
        'company_id' => $this->company->id,
        'welcome_page' => [
            'headline' => 'Welcome',
        ],
        'login_ip_whitelist' => [],
    ]);

    $this->division = Division::create([
        'company_id' => $this->company->id,
        'name' => 'Product',
        'slug' => 'product',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->department = Department::create([
        'company_id' => $this->company->id,
        'division_id' => $this->division->id,
        'name' => 'Engineering',
        'slug' => 'engineering',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->team = Team::create([
        'company_id' => $this->company->id,
        'division_id' => $this->division->id,
        'department_id' => $this->department->id,
        'name' => 'Backend',
        'slug' => 'backend',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'division_id' => $this->division->id,
        'department_id' => $this->department->id,
        'team_id' => $this->team->id,
        'role' => 'company_admin',
        'email' => 'admin@test-company.test',
    ]);

    $this->company->update(['current_user_count' => 1]);
});

it('returns tenant settings payload', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->getJson(route('api.v1.tenant.settings.show', ['company' => $this->company->slug]));

    $response->assertOk()
        ->assertJsonPath('company.slug', $this->company->slug)
        ->assertJsonPath('settings.welcome_page.headline', 'Welcome')
        ->assertJsonPath('organization.divisions.0.name', 'Product');
});

it('updates welcome page configuration', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $payload = [
        'headline' => '新的歡迎標題',
        'subheadline' => '小提醒',
        'cta' => [
            'primary' => ['label' => '登入', 'href' => 'https://tenant.test/login'],
        ],
        'highlights' => ['重點一', '重點二'],
        'steps' => [
            ['title' => '第一步', 'description' => '填寫資料'],
        ],
        'media' => ['type' => 'default'],
    ];

    $response = $this->putJson(
        route('api.v1.tenant.welcome-page.update', ['company' => $this->company->slug]),
        $payload
    );

    $response->assertOk()
        ->assertJsonPath('welcome_page.headline', '新的歡迎標題');

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $this->company->id,
        'welcome_page->headline' => '新的歡迎標題',
    ]);
});

it('validates IP whitelist entries', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $invalid = $this->putJson(
        route('api.v1.tenant.settings.ip-whitelist.update', ['company' => $this->company->slug]),
        ['entries' => ['not-an-ip']]
    );

    $invalid->assertStatus(422);

    $valid = $this->putJson(
        route('api.v1.tenant.settings.ip-whitelist.update', ['company' => $this->company->slug]),
        ['entries' => ['127.0.0.1', '10.0.0.0/8']]
    );

    $valid->assertOk()
        ->assertJsonPath('login_ip_whitelist.1', '10.0.0.0/8');
});

it('invites a new member and increments user count', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $payload = [
        'email' => 'new-member@test-company.test',
        'name' => 'New Member',
        'role' => 'member',
        'division_id' => $this->division->id,
        'department_id' => $this->department->id,
        'team_id' => $this->team->id,
    ];

    $response = $this->postJson(
        route('api.v1.tenant.members.invite', ['company' => $this->company->slug]),
        $payload
    );

    $response->assertCreated()
        ->assertJsonPath('member.role', 'member');

    $this->assertDatabaseHas('users', [
        'email' => 'new-member@test-company.test',
        'company_id' => $this->company->id,
    ]);

    expect($this->company->fresh()->current_user_count)->toBe(2);
});

it('prevents inviting members beyond the limit', function () {
    $this->company->update(['user_limit' => 1, 'current_user_count' => 1]);

    Sanctum::actingAs($this->admin, guard: 'web');

    $response = $this->postJson(
        route('api.v1.tenant.members.invite', ['company' => $this->company->slug]),
        [
            'email' => 'limit@test-company.test',
            'name' => 'Limit Hit',
            'role' => 'member',
        ]
    );

    $response->assertStatus(422);
});

it('updates member role respecting hierarchy', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $member = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'member@test-company.test',
        'role' => 'member',
    ]);

    $response = $this->patchJson(
        route('api.v1.tenant.members.roles.update', ['company' => $this->company->slug, 'member' => $member->id]),
        [
            'role' => 'team_lead',
            'team_id' => $this->team->id,
        ]
    );

    $response->assertOk()
        ->assertJsonPath('member.role', 'team_lead');

    $this->assertDatabaseHas('users', [
        'id' => $member->id,
        'team_id' => $this->team->id,
        'role' => 'team_lead',
    ]);
});

it('requires appropriate hierarchy when promoting roles', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $member = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'invalid@test-company.test',
        'role' => 'member',
    ]);

    $response = $this->patchJson(
        route('api.v1.tenant.members.roles.update', ['company' => $this->company->slug, 'member' => $member->id]),
        ['role' => 'team_lead']
    );

    $response->assertStatus(422);
});

it('returns 404 for member approval placeholder', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $member = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'pending@test-company.test',
        'role' => 'member',
    ]);

    $response = $this->postJson(
        route('api.v1.tenant.members.approve', ['company' => $this->company->slug, 'member' => $member->id])
    );

    $response->assertStatus(404);
});
