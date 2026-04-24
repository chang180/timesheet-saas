<?php

use App\Models\Company;
use App\Models\User;

test('personal users can register without a company', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Solo Worker',
        'email' => 'solo@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'solo@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->company_id)->toBeNull()
        ->and($user->role)->toBe('member')
        ->and($user->registered_via)->toBe('personal-self-register');

    $response->assertRedirect();
});

test('personal email may coexist with the same email used inside a tenant', function () {
    $company = Company::factory()->create();
    User::factory()->for($company)->create(['email' => 'shared@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Solo Worker',
        'email' => 'shared@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticated();

    expect(User::where('email', 'shared@example.com')->whereNull('company_id')->exists())->toBeTrue();
});

test('personal registration rejects duplicate personal emails', function () {
    User::factory()->create([
        'email' => 'duplicate@example.com',
        'company_id' => null,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Duplicate',
        'email' => 'duplicate@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('tenant registration still enforces global email uniqueness', function () {
    User::factory()->create([
        'email' => 'taken@example.com',
        'company_id' => null,
    ]);

    $response = $this->post(route('register.store'), [
        'company_name' => 'New Co',
        'name' => 'Conflicting',
        'email' => 'taken@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
