<?php

use App\Models\Company;
use App\Models\User;

test('tenant auth page can be rendered', function () {
    $company = Company::factory()->create([
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 5,
    ]);

    $response = $this->get(route('tenant.auth', $company));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/tenant-auth')
        ->has('company', fn ($companyData) => $companyData
            ->where('id', $company->id)
            ->where('name', $company->name)
            ->where('slug', $company->slug)
        )
        ->where('canRegister', true)
        ->where('userLimit', 10)
        ->where('currentUserCount', 5)
    );
});

test('tenant auth page shows cannot register when user limit reached', function () {
    $company = Company::factory()->create([
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 10,
    ]);

    $response = $this->get(route('tenant.auth', $company));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/tenant-auth')
        ->where('canRegister', false)
    );
});

test('tenant auth page redirects authenticated user from same company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $response = $this->actingAs($user)->get(route('tenant.auth', $company));

    $response->assertRedirect(route('tenant.home', $company));
});

test('tenant auth page redirects authenticated user from different company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company1->id,
    ]);

    $response = $this->actingAs($user)->get(route('tenant.auth', $company2));

    $response->assertRedirect(route('tenant.home', $company1));
});

test('tenant auth page redirects authenticated user without company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('tenant.auth', $company));

    $response->assertRedirect(route('app.home'));
});

test('tenant auth page returns 423 for inactive company', function () {
    $company = Company::factory()->create([
        'status' => 'suspended',
    ]);

    $response = $this->get(route('tenant.auth', $company));

    // EnsureTenantScope middleware 會檢查公司狀態，非 active 會返回 423
    $response->assertStatus(423);
});

test('users can login via tenant auth page', function () {
    $company = Company::factory()->create([
        'status' => 'active',
    ]);
    $user = User::factory()->withoutTwoFactor()->create([
        'company_id' => $company->id,
        'email' => 'test@example.com',
        'password' => bcrypt('TestPassword123!@#'),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'TestPassword123!@#',
        'company_slug' => $company->slug,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('tenant.home', $company));
});

test('users from other company cannot login via tenant auth page', function () {
    $company1 = Company::factory()->create([
        'status' => 'active',
    ]);
    $company2 = Company::factory()->create([
        'status' => 'active',
    ]);
    $user = User::factory()->withoutTwoFactor()->create([
        'company_id' => $company1->id,
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
        'company_slug' => $company2->slug,
    ]);

    // 當用戶不屬於指定公司時，authenticateUsing 會返回 null
    // 這會導致認證失敗，用戶應該保持未登入狀態
    $this->assertGuest();
});

test('users can register via tenant auth page', function () {
    $company = Company::factory()->create([
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 5,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
        'company_slug' => $company->slug,
    ]);

    $this->assertAuthenticated();
    $user = auth()->user();

    expect($user->company_id)->toBe($company->id);
    expect($user->role)->toBe('member');
    expect($user->registered_via)->toBe('tenant-register');

    $company->refresh();
    expect($company->current_user_count)->toBe(6);

    $response->assertRedirect(route('tenant.home', $company));
});

test('users cannot register when company user limit reached', function () {
    $company = Company::factory()->create([
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 10,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
        'company_slug' => $company->slug,
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('company_slug');
});

test('users cannot register for inactive company', function () {
    $company = Company::factory()->create([
        'status' => 'suspended',
        'user_limit' => 10,
        'current_user_count' => 5,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'SecureTestPass123!@#',
        'password_confirmation' => 'SecureTestPass123!@#',
        'company_slug' => $company->slug,
    ]);

    // 當公司狀態不是 active 時，CreateNewUser 會檢查並拋出 ValidationException
    $this->assertGuest();
    $response->assertSessionHasErrors('company_slug');
});

test('tenant auth page requires valid company slug', function () {
    $response = $this->get('/app/invalid-company-slug/auth');

    $response->assertStatus(404);
});

test('tenant login with non-existent company slug falls back to global login', function () {
    $company = Company::factory()->create([
        'status' => 'active',
    ]);
    $user = User::factory()->withoutTwoFactor()->create([
        'company_id' => $company->id,
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // 使用不存在的 company_slug，但因為 $tenant 會是 null，所以不會限制查詢
    // 這實際上會允許登入（因為是一般登入流程）
    $response = $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
        'company_slug' => 'non-existent-company',
    ]);

    // 因為 company_slug 不存在，$tenant 為 null，所以不會限制查詢
    // 用戶仍然可以登入（這是一般登入，不是租戶登入）
    $response->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/app');
});

test('tenant login without company_slug works for global login', function () {
    $company = Company::factory()->create([
        'status' => 'active',
    ]);
    $user = User::factory()->withoutTwoFactor()->create([
        'company_id' => $company->id,
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // 不使用 company_slug 的登入應該也能正常工作（一般登入）
    $response = $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/app');
});
