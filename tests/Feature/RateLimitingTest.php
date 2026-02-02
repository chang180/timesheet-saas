<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function createTenantUser(array $userAttributes = []): User
{
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create();

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
    ], $userAttributes));
}

it('rate limits requests based on company and user', function () {
    $user = createTenantUser();
    $company = $user->company;

    Holiday::factory()->forDate(now()->format('Y-01-01'))->create();

    for ($i = 0; $i < 120; $i++) {
        $response = actingAs($user)->getJson(route('tenant.holidays', ['company' => $company]));
        $response->assertOk();
    }

    $response = actingAs($user)->getJson(route('tenant.holidays', ['company' => $company]));
    $response->assertStatus(429);
});

it('returns correct rate limit headers', function () {
    $user = createTenantUser();
    $company = $user->company;

    Holiday::factory()->forDate(now()->format('Y-01-01'))->create();

    $response = actingAs($user)->getJson(route('tenant.holidays', ['company' => $company]));

    $response->assertOk();
    expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
    expect($response->headers->has('X-RateLimit-Remaining'))->toBeTrue();
});

it('returns correct 429 response format', function () {
    $user = createTenantUser();
    $company = $user->company;

    Holiday::factory()->forDate(now()->format('Y-01-01'))->create();

    for ($i = 0; $i < 121; $i++) {
        actingAs($user)->getJson(route('tenant.holidays', ['company' => $company]));
    }

    $response = actingAs($user)->getJson(route('tenant.holidays', ['company' => $company]));

    $response->assertStatus(429)
        ->assertJsonStructure(['message', 'retry_after']);
});

it('rate limits different users independently', function () {
    $user1 = createTenantUser();
    $user2 = createTenantUser();
    $company1 = $user1->company;
    $company2 = $user2->company;

    Holiday::factory()->forDate(now()->format('Y-01-01'))->create();

    for ($i = 0; $i < 121; $i++) {
        actingAs($user1)->getJson(route('tenant.holidays', ['company' => $company1]));
    }

    $response1 = actingAs($user1)->getJson(route('tenant.holidays', ['company' => $company1]));
    $response1->assertStatus(429);

    $response2 = actingAs($user2)->getJson(route('tenant.holidays', ['company' => $company2]));
    $response2->assertOk();
});

it('applies rate limiting to summary API', function () {
    $user = createTenantUser(['role' => 'company_admin']);
    $company = $user->company;

    for ($i = 0; $i < 121; $i++) {
        actingAs($user)->getJson(route('tenant.weekly-reports.summary', ['company' => $company]));
    }

    $response = actingAs($user)->getJson(route('tenant.weekly-reports.summary', ['company' => $company]));
    $response->assertStatus(429);
});
