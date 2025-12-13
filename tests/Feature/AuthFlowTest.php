<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('redirects authenticated user to weekly reports', function () {
    $company = Company::factory()->create(['status' => 'active', 'onboarded_at' => now()]);
    CompanySetting::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'member',
    ]);

    actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('tenant.weekly-reports', $company));
});

it('forces manager to complete setup before weekly reports', function () {
    $company = Company::factory()->create(['status' => 'active', 'onboarded_at' => null]);
    CompanySetting::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create([
        'company_id' => $company->id,
        'role' => 'company_admin',
    ]);

    actingAs($manager)
        ->get(route('tenant.weekly-reports', $company))
        ->assertRedirect(route('tenant.settings', $company));
});
