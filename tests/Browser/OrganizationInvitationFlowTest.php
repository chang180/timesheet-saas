<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithCompany(array $attributes = [], array $companyAttributes = []): User
{
    $company = Company::factory()->create(array_merge([
        'onboarded_at' => now(),
    ], $companyAttributes));
    CompanySetting::factory()->for($company)->create();

    return User::factory()->create(array_merge([
        'company_id' => $company->id,
        'email' => 'test@example.com',
        'password' => bcrypt('TestPassword123!@#'),
    ], $attributes));
}

it('可以透過 division 邀請連結完成註冊流程', function () {
    $company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'onboarded_at' => now(),
        'user_limit' => 10,
        'current_user_count' => 0,
    ]);
    CompanySetting::factory()->for($company)->create([
        'organization_levels' => ['division', 'department', 'team'],
    ]);

    $division = Division::factory()->create([
        'company_id' => $company->id,
        'name' => 'Product Division',
        'slug' => 'product',
    ]);

    $token = $division->generateInvitationToken();
    $division->enableInvitation();

    $page = visit(route('tenant.register-by-invitation', [
        'company' => $company->slug,
        'token' => $token,
        'type' => 'division',
    ]));

    $page->assertSee('加入 Test Corp')
        ->assertSee('Product Division')
        ->assertNoJavascriptErrors()
        ->fill('name', 'New User')
        ->fill('email', 'newuser@test.test')
        ->fill('password', 'Password123!')
        ->fill('password_confirmation', 'Password123!')
        ->click('完成註冊')
        ->assertNoJavascriptErrors();

    $page->wait(2);

    // 應該被導向到週報頁面
    $page->assertSee('週報工作簿')
        ->assertNoJavascriptErrors();

    // 驗證用戶已創建
    expect(User::where('email', 'newuser@test.test')->exists())->toBeTrue();
    $user = User::where('email', 'newuser@test.test')->first();
    expect($user->division_id)->toBe($division->id);
    expect($user->role)->toBe('member');
});

it('可以透過 department 邀請連結完成註冊流程', function () {
    $company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'onboarded_at' => now(),
        'user_limit' => 10,
        'current_user_count' => 0,
    ]);
    CompanySetting::factory()->for($company)->create([
        'organization_levels' => ['division', 'department', 'team'],
    ]);

    $division = Division::factory()->create([
        'company_id' => $company->id,
        'name' => 'Product Division',
    ]);

    $department = Department::factory()->create([
        'company_id' => $company->id,
        'division_id' => $division->id,
        'name' => 'Engineering',
        'slug' => 'engineering',
    ]);

    $token = $department->generateInvitationToken();
    $department->enableInvitation();

    $page = visit(route('tenant.register-by-invitation', [
        'company' => $company->slug,
        'token' => $token,
        'type' => 'department',
    ]));

    $page->assertSee('加入 Test Corp')
        ->assertSee('Engineering')
        ->assertNoJavascriptErrors()
        ->fill('name', 'New User')
        ->fill('email', 'newuser@test.test')
        ->fill('password', 'Password123!')
        ->fill('password_confirmation', 'Password123!')
        ->click('完成註冊')
        ->assertNoJavascriptErrors();

    $page->wait(2);

    $page->assertSee('週報工作簿')
        ->assertNoJavascriptErrors();

    $user = User::where('email', 'newuser@test.test')->first();
    expect($user->division_id)->toBe($division->id);
    expect($user->department_id)->toBe($department->id);
});

it('可以透過 team 邀請連結完成註冊流程', function () {
    $company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'onboarded_at' => now(),
        'user_limit' => 10,
        'current_user_count' => 0,
    ]);
    CompanySetting::factory()->for($company)->create([
        'organization_levels' => ['division', 'department', 'team'],
    ]);

    $division = Division::factory()->create([
        'company_id' => $company->id,
        'name' => 'Product Division',
    ]);

    $department = Department::factory()->create([
        'company_id' => $company->id,
        'division_id' => $division->id,
        'name' => 'Engineering',
    ]);

    $team = Team::factory()->create([
        'company_id' => $company->id,
        'division_id' => $division->id,
        'department_id' => $department->id,
        'name' => 'Backend Team',
        'slug' => 'backend',
    ]);

    $token = $team->generateInvitationToken();
    $team->enableInvitation();

    $page = visit(route('tenant.register-by-invitation', [
        'company' => $company->slug,
        'token' => $token,
        'type' => 'team',
    ]));

    $page->assertSee('加入 Test Corp')
        ->assertSee('Backend Team')
        ->assertNoJavascriptErrors()
        ->fill('name', 'New User')
        ->fill('email', 'newuser@test.test')
        ->fill('password', 'Password123!')
        ->fill('password_confirmation', 'Password123!')
        ->click('完成註冊')
        ->assertNoJavascriptErrors();

    $page->wait(2);

    $page->assertSee('週報工作簿')
        ->assertNoJavascriptErrors();

    $user = User::where('email', 'newuser@test.test')->first();
    expect($user->division_id)->toBe($division->id);
    expect($user->department_id)->toBe($department->id);
    expect($user->team_id)->toBe($team->id);
});

it('無法使用停用的邀請連結註冊', function () {
    $company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'onboarded_at' => now(),
    ]);
    CompanySetting::factory()->for($company)->create();

    $division = Division::factory()->create([
        'company_id' => $company->id,
        'name' => 'Product Division',
    ]);

    $token = $division->generateInvitationToken();
    $division->disableInvitation();

    $page = visit(route('tenant.register-by-invitation', [
        'company' => $company->slug,
        'token' => $token,
        'type' => 'division',
    ]));

    $page->assertNotFound();
});

it('無法使用無效的邀請連結註冊', function () {
    $company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'onboarded_at' => now(),
    ]);
    CompanySetting::factory()->for($company)->create();

    $page = visit(route('tenant.register-by-invitation', [
        'company' => $company->slug,
        'token' => 'invalid-token',
        'type' => 'division',
    ]));

    $page->assertNotFound();
});
