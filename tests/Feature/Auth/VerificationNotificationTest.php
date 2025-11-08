<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email_verified_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect('/app');

    Notification::assertNothingSent();
});
