<?php

use App\Models\Company;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('personal users are redirected from dashboard to personal home', function () {
    $user = User::factory()->create(['company_id' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('personal.home'));
});

test('tenant users are redirected from dashboard to tenant weekly reports', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('tenant.weekly-reports', $company));
});
