<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('global landing page can be accessed', function () {
    $response = $this->get(route('landing.global'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('landing/global-landing')
    );
});

test('tenant welcome page requires authentication', function () {
    $response = $this->get(route('tenant.welcome'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view tenant welcome page', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->get(route('tenant.welcome'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('landing/tenant-welcome')
        ->has('tenantSettings')
        ->has('welcomeConfig')
    );
});

test('tenant welcome page shows company branding', function () {
    $company = Company::factory()->create([
        'name' => 'Test Company',
        'branding' => [
            'color' => '#FF5733',
            'logo' => 'https://example.com/logo.png',
        ],
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->get(route('tenant.welcome'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('tenantSettings.companyName', 'Test Company')
        ->where('tenantSettings.brandColor', '#FF5733')
        ->where('tenantSettings.logo', 'https://example.com/logo.png')
    );
});

test('tenant welcome page shows custom welcome config', function () {
    $company = Company::factory()->create();
    CompanySetting::factory()->create([
        'company_id' => $company->id,
        'welcome_page' => [
            'hero' => [
                'enabled' => true,
                'title' => 'Custom Title',
                'subtitle' => 'Custom Subtitle',
            ],
        ],
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->get(route('tenant.welcome'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('welcomeConfig.hero.title', 'Custom Title')
        ->where('welcomeConfig.hero.subtitle', 'Custom Subtitle')
    );
});

test('tenant welcome page shows default config when none exists', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)->get(route('tenant.welcome'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('welcomeConfig.hero.title', '歡迎使用週報通')
        ->where('welcomeConfig.hero.enabled', true)
    );
});

test('tenant welcome page fails without company', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('tenant.welcome'));

    $response->assertNotFound();
});
