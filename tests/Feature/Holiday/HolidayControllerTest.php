<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createAuthenticatedUser(array $userAttributes = [], array $companyAttributes = []): User
{
    $company = Company::factory()->create(array_merge([
        'onboarded_at' => now(),
    ], $companyAttributes));
    CompanySetting::factory()->for($company)->create();

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
    ], $userAttributes));
}

beforeEach(function () {
    Cache::flush();
});

it('returns holidays for a given year', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    Holiday::factory()->forDate('2026-01-01')->create(['name' => '元旦']);
    Holiday::factory()->forDate('2026-02-28')->create(['name' => '和平紀念日']);
    Holiday::factory()->forDate('2026-10-10')->create(['name' => '國慶日']);

    $response = actingAs($user)->getJson(
        route('tenant.holidays', ['company' => $company, 'year' => 2026])
    );

    $response->assertOk()
        ->assertJsonPath('year', 2026)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name', '元旦')
        ->assertJsonPath('data.0.date', '2026-01-01')
        ->assertJsonPath('data.0.is_holiday', true);
});

it('returns holidays for current year when year is not specified', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    $currentYear = now()->year;
    Holiday::factory()->forDate("{$currentYear}-01-01")->create(['name' => '元旦']);

    $response = actingAs($user)->getJson(
        route('tenant.holidays', ['company' => $company])
    );

    $response->assertOk()
        ->assertJsonPath('year', $currentYear);
});

it('returns empty data when no holidays exist for year', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    $response = actingAs($user)->getJson(
        route('tenant.holidays', ['company' => $company, 'year' => 2025])
    );

    $response->assertOk()
        ->assertJsonPath('year', 2025)
        ->assertJsonCount(0, 'data');
});

it('returns 422 for invalid year', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    $response = actingAs($user)->getJson(
        route('tenant.holidays', ['company' => $company, 'year' => 2019])
    );

    $response->assertStatus(422);
});

it('returns holidays for a given ISO week', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    $monday = Carbon::now()->setISODate(2026, 5, 1);
    $friday = Carbon::now()->setISODate(2026, 5, 5);

    Holiday::factory()->forDate($monday)->create(['name' => '週一假期']);
    Holiday::factory()->forDate($friday)->create(['name' => '週五假期']);
    Holiday::factory()->forDate('2026-02-28')->create(['name' => '不在本週']);

    $response = actingAs($user)->getJson(
        route('tenant.holidays.week', ['company' => $company, 'year' => 2026, 'week' => 5])
    );

    $response->assertOk()
        ->assertJsonPath('year', 2026)
        ->assertJsonPath('week', 5)
        ->assertJsonCount(2, 'data');
});

it('returns empty data when no holidays exist in the week', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    $response = actingAs($user)->getJson(
        route('tenant.holidays.week', ['company' => $company, 'year' => 2026, 'week' => 10])
    );

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns 422 for invalid week number', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    $response = actingAs($user)->getJson(
        route('tenant.holidays.week', ['company' => $company, 'year' => 2026, 'week' => 55])
    );

    $response->assertStatus(422);
});

it('returns correct holiday categories', function () {
    $user = createAuthenticatedUser();
    $company = $user->company;

    Holiday::factory()->forDate('2026-01-01')->national()->create(['name' => '元旦']);
    Holiday::factory()->forDate('2026-01-03')->weekend()->create();
    Holiday::factory()->forDate('2026-01-17')->makeupWorkday()->create();

    $response = actingAs($user)->getJson(
        route('tenant.holidays', ['company' => $company, 'year' => 2026])
    );

    $response->assertOk();
    $data = $response->json('data');

    expect(collect($data)->firstWhere('date', '2026-01-01')['category'])->toBe('national');
    expect(collect($data)->firstWhere('date', '2026-01-03')['category'])->toBe('weekend');
    expect(collect($data)->firstWhere('date', '2026-01-17')['category'])->toBe('makeup_workday');
    expect(collect($data)->firstWhere('date', '2026-01-17')['is_workday_override'])->toBeTrue();
});

it('requires authentication', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create();

    $response = $this->getJson(route('tenant.holidays', ['company' => $company]));

    $response->assertUnauthorized();
});
