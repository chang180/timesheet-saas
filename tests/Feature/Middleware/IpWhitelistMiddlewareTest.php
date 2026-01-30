<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Support\IpMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('allows request when whitelist is empty', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create([
        'login_ip_whitelist' => [],
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->get(route('tenant.weekly-reports', $company));

    $response->assertOk();
});

it('allows request when IP matches whitelist', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create([
        'login_ip_whitelist' => ['127.0.0.1', '192.168.1.100'],
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->get(route('tenant.weekly-reports', $company));

    $response->assertOk();
});

it('blocks request when IP is not in whitelist', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create([
        'login_ip_whitelist' => ['10.0.0.1', '10.0.0.2'],
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    // Apply the IP whitelist middleware to the route
    Route::get('/test-ip-whitelist/{company:slug}', fn () => 'OK')
        ->middleware(['tenant', 'ip.whitelist']);

    $response = $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
        ->get("/test-ip-whitelist/{$company->slug}");

    $response->assertForbidden();
});

it('allows request when IP matches CIDR range', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create([
        'login_ip_whitelist' => ['192.168.1.0/24'],
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    Route::get('/test-ip-cidr/{company:slug}', fn () => 'OK')
        ->middleware(['tenant', 'ip.whitelist']);

    $response = $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
        ->get("/test-ip-cidr/{$company->slug}");

    $response->assertOk();
});

it('blocks request when IP is outside CIDR range', function () {
    $company = Company::factory()->create(['onboarded_at' => now()]);
    CompanySetting::factory()->for($company)->create([
        'login_ip_whitelist' => ['192.168.1.0/24'],
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    Route::get('/test-ip-cidr-block/{company:slug}', fn () => 'OK')
        ->middleware(['tenant', 'ip.whitelist']);

    $response = $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '192.168.2.1'])
        ->get("/test-ip-cidr-block/{$company->slug}");

    $response->assertForbidden();
});

describe('IpMatcher', function () {
    it('returns true when rules are empty', function () {
        expect(IpMatcher::matches('192.168.1.1', []))->toBeTrue();
    });

    it('matches exact IP', function () {
        expect(IpMatcher::matches('192.168.1.1', ['192.168.1.1']))->toBeTrue();
        expect(IpMatcher::matches('192.168.1.2', ['192.168.1.1']))->toBeFalse();
    });

    it('matches CIDR /24 range', function () {
        expect(IpMatcher::matches('192.168.1.1', ['192.168.1.0/24']))->toBeTrue();
        expect(IpMatcher::matches('192.168.1.255', ['192.168.1.0/24']))->toBeTrue();
        expect(IpMatcher::matches('192.168.2.1', ['192.168.1.0/24']))->toBeFalse();
    });

    it('matches CIDR /16 range', function () {
        expect(IpMatcher::matches('192.168.1.1', ['192.168.0.0/16']))->toBeTrue();
        expect(IpMatcher::matches('192.168.255.255', ['192.168.0.0/16']))->toBeTrue();
        expect(IpMatcher::matches('192.169.1.1', ['192.168.0.0/16']))->toBeFalse();
    });

    it('matches CIDR /32 (exact match)', function () {
        expect(IpMatcher::matches('192.168.1.1', ['192.168.1.1/32']))->toBeTrue();
        expect(IpMatcher::matches('192.168.1.2', ['192.168.1.1/32']))->toBeFalse();
    });

    it('matches CIDR /0 (all IPs)', function () {
        expect(IpMatcher::matches('192.168.1.1', ['0.0.0.0/0']))->toBeTrue();
        expect(IpMatcher::matches('10.0.0.1', ['0.0.0.0/0']))->toBeTrue();
    });

    it('matches any of multiple rules', function () {
        $rules = ['10.0.0.1', '192.168.0.0/16', '172.16.0.0/12'];

        expect(IpMatcher::matches('10.0.0.1', $rules))->toBeTrue();
        expect(IpMatcher::matches('192.168.1.50', $rules))->toBeTrue();
        expect(IpMatcher::matches('172.16.5.10', $rules))->toBeTrue();
        expect(IpMatcher::matches('8.8.8.8', $rules))->toBeFalse();
    });

    it('handles invalid CIDR gracefully', function () {
        expect(IpMatcher::matches('192.168.1.1', ['invalid']))->toBeFalse();
        expect(IpMatcher::matches('192.168.1.1', ['192.168.1.1/invalid']))->toBeFalse();
        expect(IpMatcher::matches('192.168.1.1', ['192.168.1.1/33']))->toBeFalse();
    });

    it('handles IPv6 addresses', function () {
        expect(IpMatcher::matches('::1', ['::1']))->toBeTrue();
        expect(IpMatcher::matches('2001:db8::1', ['2001:db8::/32']))->toBeTrue();
        expect(IpMatcher::matches('2001:db9::1', ['2001:db8::/32']))->toBeFalse();
    });
});
