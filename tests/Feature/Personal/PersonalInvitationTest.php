<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;

test('personal user can view email invitation page', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);

    User::factory()->for($company)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'test-email-token',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
        'role' => 'member',
    ]);

    $this->actingAs($personalUser)
        ->get(route('personal.invitations.accept', ['token' => 'test-email-token']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('personal/invitations/accept-email')
            ->where('token', 'test-email-token')
            ->where('company.name', $company->name)
        );
});

test('personal user can accept email invitation and join company', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);

    User::factory()->for($company)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'accept-token',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
        'role' => 'member',
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.accept.store', ['token' => 'accept-token']))
        ->assertRedirect(route('tenant.weekly-reports', $company));

    $fresh = $personalUser->fresh();
    expect($fresh->company_id)->toBe($company->id);
    expect($fresh->role)->toBe('member');
    expect($company->fresh()->current_user_count)->toBe(2);
});

test('accepting email invitation removes invite placeholder record', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);

    $invite = User::factory()->for($company)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'accept-token',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
        'role' => 'member',
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.accept.store', ['token' => 'accept-token']));

    expect(User::withoutGlobalScopes()->find($invite->id))->toBeNull();
});

test('accepting one email invitation invalidates other pending invitations for same email', function () {
    $companyA = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $companyB = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);

    User::factory()->for($companyA)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'token-company-a',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    $inviteB = User::factory()->for($companyB)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'token-company-b',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.accept.store', ['token' => 'token-company-a']))
        ->assertRedirect();

    $freshB = User::withoutGlobalScopes()->find($inviteB->id);
    expect($freshB->invitation_token)->toBeNull();
    expect($freshB->invitation_revoked_at)->not->toBeNull();
});

test('invalidation writes audit log for revoked invitations', function () {
    $companyA = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $companyB = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);

    User::factory()->for($companyA)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'token-a',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    User::factory()->for($companyB)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'token-b',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.accept.store', ['token' => 'token-a']));

    expect(AuditLog::query()
        ->where('event', 'invitation.invalidated_by_join')
        ->where('user_id', $personalUser->id)
        ->exists())->toBeTrue();
});

test('cannot accept email invitation when company is full', function () {
    $company = Company::factory()->create(['current_user_count' => 5, 'user_limit' => 5]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);

    User::factory()->for($company)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'full-company-token',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.accept.store', ['token' => 'full-company-token']))
        ->assertSessionHasErrors('company');

    expect($personalUser->fresh()->company_id)->toBeNull();
});

test('email invitation returns 404 if token does not match user email', function () {
    $company = Company::factory()->create();
    $personalUser = User::factory()->create(['company_id' => null]);

    User::factory()->for($company)->create([
        'email' => 'other@example.com',
        'invitation_token' => 'someone-elses-token',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    $this->actingAs($personalUser)
        ->get(route('personal.invitations.accept', ['token' => 'someone-elses-token']))
        ->assertNotFound();
});

test('expired email invitation returns 410', function () {
    $company = Company::factory()->create(['user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null]);

    User::factory()->for($company)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'expired-token',
        'invitation_sent_at' => now()->subDays(8),
        'invitation_accepted_at' => null,
    ]);

    $this->actingAs($personalUser)
        ->get(route('personal.invitations.accept', ['token' => 'expired-token']))
        ->assertStatus(410);
});

test('user with company_id cannot access personal invitation routes', function () {
    $company = Company::factory()->create(['user_limit' => 10]);
    $tenantUser = User::factory()->for($company)->create(['role' => 'member']);

    $this->actingAs($tenantUser)
        ->get(route('personal.invitations.accept', ['token' => 'any-token']))
        ->assertRedirect();
});

test('personal user can view division invitation page', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);
    Division::factory()->for($company)->create([
        'invitation_token' => 'div-token',
        'invitation_enabled' => true,
    ]);

    $this->actingAs($personalUser)
        ->get(route('personal.invitations.join', ['company' => $company->slug, 'token' => 'div-token', 'type' => 'division']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('personal/invitations/accept-org')
            ->where('organization.type', 'division')
        );
});

test('personal user can accept division invitation', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null, 'registered_via' => 'personal-self-register']);
    $division = Division::factory()->for($company)->create([
        'invitation_token' => 'div-join-token',
        'invitation_enabled' => true,
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.join.store', ['company' => $company->slug, 'token' => 'div-join-token', 'type' => 'division']))
        ->assertRedirect(route('tenant.weekly-reports', $company));

    $fresh = $personalUser->fresh();
    expect($fresh->company_id)->toBe($company->id);
    expect($fresh->division_id)->toBe($division->id);
    expect($company->fresh()->current_user_count)->toBe(2);
});

test('personal user can accept department invitation and gets division set too', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null]);
    $division = Division::factory()->for($company)->create();
    $department = Department::factory()->for($company)->for($division)->create([
        'invitation_token' => 'dept-token',
        'invitation_enabled' => true,
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.join.store', ['company' => $company->slug, 'token' => 'dept-token', 'type' => 'department']));

    $fresh = $personalUser->fresh();
    expect($fresh->company_id)->toBe($company->id);
    expect($fresh->department_id)->toBe($department->id);
    expect($fresh->division_id)->toBe($division->id);
});

test('personal user can accept team invitation and gets department and division set too', function () {
    $company = Company::factory()->create(['current_user_count' => 1, 'user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null]);
    $division = Division::factory()->for($company)->create();
    $department = Department::factory()->for($company)->for($division)->create();
    $team = Team::factory()->for($company)->for($department)->for($division)->create([
        'invitation_token' => 'team-token',
        'invitation_enabled' => true,
    ]);

    $this->actingAs($personalUser)
        ->post(route('personal.invitations.join.store', ['company' => $company->slug, 'token' => 'team-token', 'type' => 'team']));

    $fresh = $personalUser->fresh();
    expect($fresh->company_id)->toBe($company->id);
    expect($fresh->team_id)->toBe($team->id);
    expect($fresh->department_id)->toBe($department->id);
    expect($fresh->division_id)->toBe($division->id);
});

test('disabled org invitation returns 404', function () {
    $company = Company::factory()->create(['user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null]);
    Division::factory()->for($company)->create([
        'invitation_token' => 'disabled-token',
        'invitation_enabled' => false,
    ]);

    $this->actingAs($personalUser)
        ->get(route('personal.invitations.join', ['company' => $company->slug, 'token' => 'disabled-token', 'type' => 'division']))
        ->assertNotFound();
});

test('logged-in personal user visiting tenant email invite page is redirected to personal route', function () {
    $company = Company::factory()->create(['user_limit' => 10]);
    $personalUser = User::factory()->create(['company_id' => null]);

    User::factory()->for($company)->create([
        'email' => $personalUser->email,
        'invitation_token' => 'tenant-token',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    $this->actingAs($personalUser)
        ->get(route('tenant.invitations.accept', ['company' => $company->slug, 'token' => 'tenant-token']))
        ->assertRedirect(route('personal.invitations.accept', ['token' => 'tenant-token']));
});
