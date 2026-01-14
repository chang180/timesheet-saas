<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Test Corp',
        'slug' => 'test-corp',
        'status' => 'active',
        'user_limit' => 10,
        'current_user_count' => 0,
        'timezone' => 'Asia/Taipei',
    ]);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'company_admin',
        'email' => 'admin@test.test',
    ]);

    $this->company->update(['current_user_count' => 1]);

    Sanctum::actingAs($this->admin, guard: 'web');
});

it('公司管理者可以新增部門並 redirect 回組織管理', function (): void {
    $response = $this->post(
        route('tenant.departments.store', ['company' => $this->company->slug]),
        [
            'name' => 'Engineering',
            'is_active' => true,
        ]
    );

    $response->assertRedirect(route('tenant.organization', ['company' => $this->company->slug]));

    $this->assertDatabaseHas('departments', [
        'company_id' => $this->company->id,
        'name' => 'Engineering',
        'is_active' => 1,
    ]);

    $department = Department::query()
        ->where('company_id', $this->company->id)
        ->where('name', 'Engineering')
        ->first();

    expect($department)->not->toBeNull();
    expect($department->slug)->toBe('engineering');
});
