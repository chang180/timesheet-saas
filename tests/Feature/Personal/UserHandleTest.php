<?php

use App\Models\User;

test('user can set a valid handle', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->put(route('settings.handle.update'), [
        'handle' => 'alice_01',
    ]);

    $response->assertRedirect(route('settings.handle.show'));
    expect($user->fresh()->handle)->toBe('alice_01');
});

test('handle is normalized to lowercase and trimmed', function () {
    $user = User::factory()->create(['company_id' => null]);

    $this->actingAs($user)->put(route('settings.handle.update'), [
        'handle' => '  Alice_01  ',
    ])->assertRedirect();

    expect($user->fresh()->handle)->toBe('alice_01');
});

test('handle rejects invalid regex', function () {
    $user = User::factory()->create(['company_id' => null]);

    foreach (['ab', 'too-long-handle-xxxxxxxxxxxxxxxxxxxxxx', 'has space', 'UPPER@x', 'bad.char'] as $bad) {
        $this->actingAs($user)->from(route('settings.handle.show'))
            ->put(route('settings.handle.update'), ['handle' => $bad])
            ->assertSessionHasErrors(['handle']);
    }
});

test('handle rejects reserved names', function () {
    $user = User::factory()->create(['company_id' => null]);

    foreach (['admin', 'api', 'login', 'settings', 'u', 'me'] as $reserved) {
        $this->actingAs($user)->from(route('settings.handle.show'))
            ->put(route('settings.handle.update'), ['handle' => $reserved])
            ->assertSessionHasErrors(['handle']);
    }
});

test('handle must be unique across users', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'taken']);
    $bob = User::factory()->create(['company_id' => null]);

    $this->actingAs($bob)->from(route('settings.handle.show'))
        ->put(route('settings.handle.update'), ['handle' => 'taken'])
        ->assertSessionHasErrors(['handle']);
});

test('user can resubmit the same handle they already own', function () {
    $user = User::factory()->create(['company_id' => null, 'handle' => 'mine']);

    $this->actingAs($user)->put(route('settings.handle.update'), ['handle' => 'mine'])
        ->assertRedirect(route('settings.handle.show'));
});

test('checkAvailability returns available true for valid handle', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('settings.handle.check', ['handle' => 'fresh_handle']));

    $response->assertOk();
    $response->assertExactJson(['available' => true]);
});

test('checkAvailability returns reason=reserved for reserved words', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('settings.handle.check', ['handle' => 'admin']));

    $response->assertOk();
    $response->assertExactJson(['available' => false, 'reason' => 'reserved']);
});

test('checkAvailability returns reason=taken for used handles', function () {
    User::factory()->create(['company_id' => null, 'handle' => 'popular']);
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('settings.handle.check', ['handle' => 'popular']));

    $response->assertOk();
    $response->assertExactJson(['available' => false, 'reason' => 'taken']);
});

test('checkAvailability returns reason=invalid for bad format', function () {
    $user = User::factory()->create(['company_id' => null]);

    $response = $this->actingAs($user)->get(route('settings.handle.check', ['handle' => 'AB']));

    $response->assertOk();
    $response->assertExactJson(['available' => false, 'reason' => 'invalid']);
});

test('checkAvailability requires authentication', function () {
    $this->get(route('settings.handle.check', ['handle' => 'anything']))
        ->assertRedirect(route('login'));
});
