<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->hqAdmin = User::factory()->create([
        'role' => 'hq_admin',
        'email' => 'hq.admin@example.com',
    ]);

    $this->regularUser = User::factory()->create([
        'role' => 'member',
        'email' => 'member@example.com',
    ]);

    $this->companyAdmin = User::factory()->create([
        'role' => 'company_admin',
        'email' => 'company.admin@example.com',
    ]);
});

describe('HQ Portal Authorization', function () {
    it('allows hq_admin to access HQ endpoints', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $response = $this->getJson(route('api.v1.hq.companies.index'));

        $response->assertOk();
    });

    it('returns 403 for regular member accessing HQ endpoints', function () {
        Sanctum::actingAs($this->regularUser, guard: 'web');

        $response = $this->getJson(route('api.v1.hq.companies.index'));

        $response->assertForbidden();
    });

    it('returns 403 for company_admin accessing HQ endpoints', function () {
        Sanctum::actingAs($this->companyAdmin, guard: 'web');

        $response = $this->getJson(route('api.v1.hq.companies.index'));

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated users', function () {
        $response = $this->getJson(route('api.v1.hq.companies.index'));

        $response->assertUnauthorized();
    });
});

describe('GET /api/v1/hq/companies', function () {
    it('lists all companies with pagination', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        Company::factory()->count(3)->create();

        $response = $this->getJson(route('api.v1.hq.companies.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'status', 'user_limit', 'current_user_count'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 3);
    });

    it('respects per_page parameter', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        Company::factory()->count(5)->create();

        $response = $this->getJson(route('api.v1.hq.companies.index', ['per_page' => 2]));

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    });
});

describe('POST /api/v1/hq/companies', function () {
    it('creates a new company successfully', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $payload = [
            'name' => 'New Company',
            'slug' => 'new-company',
            'user_limit' => 100,
        ];

        $response = $this->postJson(route('api.v1.hq.companies.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Company')
            ->assertJsonPath('data.slug', 'new-company')
            ->assertJsonPath('data.user_limit', 100)
            ->assertJsonPath('data.status', 'onboarding');

        $this->assertDatabaseHas('companies', [
            'name' => 'New Company',
            'slug' => 'new-company',
        ]);

        $this->assertDatabaseHas('company_settings', [
            'company_id' => $response->json('data.id'),
        ]);
    });

    it('creates company with default user_limit of 50', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $payload = [
            'name' => 'Default Limit Company',
            'slug' => 'default-limit',
        ];

        $response = $this->postJson(route('api.v1.hq.companies.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.user_limit', 50);
    });

    it('enforces unique slug constraint', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        Company::factory()->create(['slug' => 'existing-slug']);

        $payload = [
            'name' => 'Another Company',
            'slug' => 'existing-slug',
        ];

        $response = $this->postJson(route('api.v1.hq.companies.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

    it('validates slug format', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $payload = [
            'name' => 'Company',
            'slug' => 'Invalid Slug!',
        ];

        $response = $this->postJson(route('api.v1.hq.companies.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

    it('requires name and slug', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $response = $this->postJson(route('api.v1.hq.companies.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    });

    it('can assign an admin user to the company', function () {
        $userToAssign = User::factory()->create([
            'role' => 'member',
            'company_id' => null,
        ]);

        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $payload = [
            'name' => 'Company With Admin',
            'slug' => 'company-with-admin',
            'admin_user_id' => $userToAssign->id,
        ];

        $response = $this->postJson(route('api.v1.hq.companies.store'), $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'id' => $userToAssign->id,
            'company_id' => $response->json('data.id'),
            'role' => 'company_admin',
        ]);

        expect(Company::find($response->json('data.id'))->current_user_count)->toBe(1);
    });
});

describe('GET /api/v1/hq/companies/{company}', function () {
    it('returns company details', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create([
            'name' => 'Detail Company',
            'slug' => 'detail-company',
        ]);

        CompanySetting::create([
            'company_id' => $company->id,
            'welcome_page' => ['hero' => ['title' => 'Welcome']],
            'login_ip_whitelist' => ['192.168.1.0/24'],
        ]);

        $response = $this->getJson(route('api.v1.hq.companies.show', $company));

        $response->assertOk()
            ->assertJsonPath('data.name', 'Detail Company')
            ->assertJsonPath('data.slug', 'detail-company')
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'slug', 'status', 'user_limit',
                    'current_user_count', 'timezone', 'branding',
                    'settings' => ['welcome_page', 'login_ip_whitelist'],
                ],
            ]);
    });

    it('returns 404 for non-existent company', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $response = $this->getJson(route('api.v1.hq.companies.show', ['company' => 'non-existent']));

        $response->assertNotFound();
    });
});

describe('PATCH /api/v1/hq/companies/{company}', function () {
    it('updates company status', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create(['status' => 'active']);

        $response = $this->patchJson(
            route('api.v1.hq.companies.update', $company),
            ['status' => 'suspended']
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 'suspended',
        ]);

        expect(Company::find($company->id)->suspended_at)->not->toBeNull();
    });

    it('updates company user_limit', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create(['user_limit' => 50]);

        $response = $this->patchJson(
            route('api.v1.hq.companies.update', $company),
            ['user_limit' => 200]
        );

        $response->assertOk()
            ->assertJsonPath('data.user_limit', 200);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'user_limit' => 200,
        ]);
    });

    it('updates company branding', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create();

        $response = $this->patchJson(
            route('api.v1.hq.companies.update', $company),
            [
                'branding' => [
                    'logo_url' => 'https://example.com/logo.png',
                    'primary_color' => '#FF5733',
                ],
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.branding.logo_url', 'https://example.com/logo.png')
            ->assertJsonPath('data.branding.primary_color', '#FF5733');
    });

    it('validates status values', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create();

        $response = $this->patchJson(
            route('api.v1.hq.companies.update', $company),
            ['status' => 'invalid-status']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    it('clears suspended_at when status changes from suspended', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $response = $this->patchJson(
            route('api.v1.hq.companies.update', $company),
            ['status' => 'active']
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.suspended_at', null);
    });
});

describe('PATCH /api/v1/hq/companies/{company}/user-limit', function () {
    it('updates only the user_limit', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create([
            'user_limit' => 50,
            'name' => 'Original Name',
        ]);

        $response = $this->patchJson(
            route('api.v1.hq.companies.user-limit.update', $company),
            ['user_limit' => 150]
        );

        $response->assertOk()
            ->assertJsonPath('data.user_limit', 150);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'user_limit' => 150,
            'name' => 'Original Name',
        ]);
    });

    it('requires user_limit field', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create();

        $response = $this->patchJson(
            route('api.v1.hq.companies.user-limit.update', $company),
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_limit']);
    });

    it('validates user_limit range', function () {
        Sanctum::actingAs($this->hqAdmin, guard: 'web');

        $company = Company::factory()->create();

        $response = $this->patchJson(
            route('api.v1.hq.companies.user-limit.update', $company),
            ['user_limit' => 0]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_limit']);
    });
});
