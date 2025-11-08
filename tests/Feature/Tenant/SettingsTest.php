<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Inertia\Testing\AssertableInertia as Assert;
test('tenant settings page renders welcome configuration', function () {
    $company = Company::factory()->has(
        CompanySetting::factory()->state([
            'welcome_page' => [
                'hero' => [
                    'enabled' => true,
                    'title' => '歡迎 {company}',
                ],
                'quickStartSteps' => [
                    'enabled' => true,
                    'steps' => [
                        ['title' => '登入', 'description' => '使用公司信箱登入'],
                    ],
                ],
            ],
        ]),
        'settings'
    )->create([
        'name' => 'Acme Corp',
        'branding' => [
            'primaryColor' => '#123456',
            'logoUrl' => 'https://acme.test/logo.png',
        ],
        'user_limit' => 10,
    ]);

    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('tenant.settings', $company));

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
        ->component('tenant/settings/index')
        ->where('settings.companyName', 'Acme Corp')
        ->where('settings.brandColor', '#123456')
        ->where('settings.welcomePage.hero.title', '歡迎 {company}')
        ->where('settings.ipWhitelist', [])
    );
});

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
            'hero' => [
                'enabled' => true,
                'title' => 'Welcome to Timesheet SaaS',
                'subtitle' => 'Manage weekly reports effortlessly.',
            ],
            'quickStartSteps' => [
                'enabled' => true,
                'steps' => [
                    ['title' => '登入系統', 'description' => '使用公司信箱登入用戶後台。'],
                ],
            ],
            'weeklyReportDemo' => [
                'enabled' => true,
                'highlights' => [
                    '拖曳排序同步更新主管檢視順序',
                    'Redmine/Jira 自動帶入任務',
                ],
            ],
            'announcements' => [
                'enabled' => false,
                'items' => [],
            ],
            'supportContacts' => [
                'enabled' => false,
                'contacts' => [],
            ],
            'ctas' => [
                ['text' => '立即登入', 'url' => 'https://tenant.test/login', 'variant' => 'primary'],
            ],
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
        ->assertJsonPath('settings.welcome_page.hero.title', 'Welcome to Timesheet SaaS')
        ->assertJsonPath('organization.divisions.0.name', 'Product');
});

it('updates welcome page configuration', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

    $payload = [
        'hero' => [
            'enabled' => true,
            'title' => '新的歡迎標題',
            'subtitle' => '小提醒',
        ],
        'quickStartSteps' => [
            'enabled' => true,
            'steps' => [
                ['title' => '第一步', 'description' => '填寫資料'],
            ],
        ],
        'announcements' => [
            'enabled' => false,
            'items' => [],
        ],
        'supportContacts' => [
            'enabled' => false,
            'contacts' => [],
        ],
        'ctas' => [
            ['text' => '登入', 'url' => 'https://tenant.test/login'],
        ],
    ];

    $response = $this->putJson(
        route('api.v1.tenant.welcome-page.update', ['company' => $this->company->slug]),
        $payload
    );

    $response->assertOk()
        ->assertJsonPath('welcome_page.hero.title', '新的歡迎標題');

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $this->company->id,
        'welcome_page->hero->title' => '新的歡迎標題',
    ]);

    expect($this->company->fresh()->onboarded_at)->not->toBeNull();
});

it('validates IP whitelist entries', function () {
    Sanctum::actingAs($this->admin, guard: 'web');

$invalid = $this->putJson(
    route('api.v1.tenant.settings.ip-whitelist.update', ['company' => $this->company->slug]),
    ['ipAddresses' => ['not-an-ip']]
);

    $invalid->assertStatus(422);

$valid = $this->putJson(
    route('api.v1.tenant.settings.ip-whitelist.update', ['company' => $this->company->slug]),
    ['ipAddresses' => ['127.0.0.1', '10.0.0.0/8']]
);

    $valid->assertOk()
        ->assertJsonPath('login_ip_whitelist.1', '10.0.0.0/8');

    expect($this->company->fresh()->onboarded_at)->not->toBeNull();
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
