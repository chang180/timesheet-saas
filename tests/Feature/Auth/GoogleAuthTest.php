<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

beforeEach(function () {
    // 在本機環境（.test 網域）跳過測試
    if (app()->environment('local') && str_contains(config('app.url'), '.test')) {
        $this->markTestSkipped('Google OAuth tests are skipped in local .test environment');
    }

    // Feature 測試需要 array session driver（cookie driver 與 withSession() 不相容）
    config(['session.driver' => 'array']);
});

test('google oauth redirect route is accessible', function () {
    $response = $this->get(route('google.redirect'));

    // 應該重定向到 Google OAuth 頁面
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('google oauth callback creates new user for regular registration', function () {
    // Mock Socialite user
    $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
    $googleUser->shouldReceive('getId')->andReturn('google-123');
    $googleUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $googleUser->shouldReceive('getName')->andReturn('Test User');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    // 設定註冊意圖（使用 withSession 確保 session 正確設定）
    $response = $this->withSession(['google_auth_intent' => 'register'])
        ->get(route('google.callback'));

    // 應該建立新用戶並登入
    $this->assertAuthenticated();
    $user = auth()->user();
    $user->refresh(); // 重新載入以確保取得最新資料

    expect($user->email)->toBe('test@example.com');
    expect($user->name)->toBe('Test User');
    expect($user->google_id)->toBe('google-123');
    expect($user->avatar)->toBe('https://example.com/avatar.jpg');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->company)->not->toBeNull();
});

test('google oauth callback links to existing user with same email', function () {
    $company = Company::factory()->create();
    $existingUser = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    // Mock Socialite user with same email
    $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
    $googleUser->shouldReceive('getId')->andReturn('google-456');
    $googleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $googleUser->shouldReceive('getName')->andReturn('Updated Name');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-avatar.jpg');

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $response = $this->get(route('google.callback'));

    // 應該連結到現有用戶
    $this->assertAuthenticated();
    $existingUser->refresh();

    expect($existingUser->google_id)->toBe('google-456');
    expect($existingUser->avatar)->toBe('https://example.com/new-avatar.jpg');
    expect(auth()->id())->toBe($existingUser->id);
});

test('google oauth callback creates tenant user', function () {
    $company = Company::factory()->create([
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 5,
    ]);

    // Mock Socialite user
    $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
    $googleUser->shouldReceive('getId')->andReturn('google-789');
    $googleUser->shouldReceive('getEmail')->andReturn('tenant@example.com');
    $googleUser->shouldReceive('getName')->andReturn('Tenant User');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    // 設定租戶註冊意圖（使用 withSession 確保 session 正確設定）
    $response = $this->withSession([
        'google_auth_intent' => 'tenant_register',
        'google_auth_company_slug' => $company->slug,
    ])->get(route('google.callback'));

    // 應該建立租戶用戶並登入
    $this->assertAuthenticated();
    $user = auth()->user();

    expect($user->email)->toBe('tenant@example.com');
    expect($user->company_id)->toBe($company->id);
    expect($user->role)->toBe('member');
    expect($user->registered_via)->toBe('google-tenant-register');

    $company->refresh();
    expect($company->current_user_count)->toBe(6);
});

test('google oauth callback accepts invitation', function () {
    $company = Company::factory()->create();
    $invitedUser = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'invited@example.com',
        'invitation_token' => 'test-token-123',
        'invitation_sent_at' => now(),
        'invitation_accepted_at' => null,
    ]);

    // Mock Socialite user with matching email
    $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
    $googleUser->shouldReceive('getId')->andReturn('google-invited');
    $googleUser->shouldReceive('getEmail')->andReturn('invited@example.com');
    $googleUser->shouldReceive('getName')->andReturn('Invited User');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    // 設定邀請意圖（使用 withSession 確保 session 正確設定）
    $response = $this->withSession([
        'google_auth_intent' => 'invitation',
        'google_auth_company_slug' => $company->slug,
        'google_auth_invitation_token' => 'test-token-123',
    ])->get(route('google.callback'));

    // 應該接受邀請並登入
    $this->assertAuthenticated();

    // 使用登入的用戶 ID 重新載入資料
    $loggedInUser = User::find(auth()->id());

    expect($loggedInUser->google_id)->toBe('google-invited');
    expect($loggedInUser->invitation_token)->toBeNull();
    expect($loggedInUser->invitation_accepted_at)->not->toBeNull();
    expect($loggedInUser->id)->toBe($invitedUser->id);
});

test('google oauth callback handles organization invitation', function () {
    $company = Company::factory()->create([
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 5,
    ]);

    $division = \App\Models\Division::factory()->create([
        'company_id' => $company->id,
        'invitation_token' => 'org-token-123',
        'invitation_enabled' => true,
    ]);

    // Mock Socialite user
    $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
    $googleUser->shouldReceive('getId')->andReturn('google-org');
    $googleUser->shouldReceive('getEmail')->andReturn('org@example.com');
    $googleUser->shouldReceive('getName')->andReturn('Org User');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    // 設定組織邀請意圖（使用 withSession 確保 session 正確設定）
    $response = $this->withSession([
        'google_auth_intent' => 'organization_invitation',
        'google_auth_organization_invitation' => [
            'company_slug' => $company->slug,
            'token' => 'org-token-123',
            'type' => 'division',
        ],
    ])->get(route('google.callback'));

    // 應該建立組織用戶並登入
    $this->assertAuthenticated();
    $user = auth()->user();

    expect($user->email)->toBe('org@example.com');
    expect($user->company_id)->toBe($company->id);
    expect($user->division_id)->toBe($division->id);
    expect($user->role)->toBe('member');
    expect($user->registered_via)->toBe('google-invitation-link');
});

test('google oauth redirect stores registration context in session', function () {
    $company = Company::factory()->create();

    $response = $this->get(route('google.redirect', [
        'intent' => 'tenant_register',
        'company_slug' => $company->slug,
    ]));

    expect(Session::get('google_auth_intent'))->toBe('tenant_register');
    expect(Session::get('google_auth_company_slug'))->toBe($company->slug);
});

test('user model has google account check method', function () {
    $userWithGoogle = User::factory()->create([
        'google_id' => 'google-123',
    ]);

    $userWithoutGoogle = User::factory()->create([
        'google_id' => null,
    ]);

    expect($userWithGoogle->hasGoogleAccount())->toBeTrue();
    expect($userWithoutGoogle->hasGoogleAccount())->toBeFalse();
});
