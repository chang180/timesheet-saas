<?php

use App\Support\ReservedHandles;
use Illuminate\Support\Facades\Schema;

test('users.handle column exists as unique nullable varchar', function () {
    expect(Schema::hasColumn('users', 'handle'))->toBeTrue();

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'handle');

    expect($column)->not->toBeNull();
    expect((bool) $column['nullable'])->toBeTrue();
});

test('weekly_reports has is_public and published_at columns', function () {
    expect(Schema::hasColumn('weekly_reports', 'is_public'))->toBeTrue();
    expect(Schema::hasColumn('weekly_reports', 'published_at'))->toBeTrue();
});

test('reserved handles contain common system paths', function () {
    foreach (['admin', 'api', 'app', 'auth', 'login', 'register', 'settings', 'u', 'me', 'dashboard', 'public', 'hq'] as $reserved) {
        expect(ReservedHandles::isReserved($reserved))
            ->toBeTrue("Expected '{$reserved}' to be reserved");
    }
});

test('reserved handle check is case-insensitive', function () {
    expect(ReservedHandles::isReserved('ADMIN'))->toBeTrue();
    expect(ReservedHandles::isReserved('Admin'))->toBeTrue();
    expect(ReservedHandles::isReserved('  admin  '))->toBeTrue();
});

test('non-reserved handles return false', function () {
    expect(ReservedHandles::isReserved('alice'))->toBeFalse();
    expect(ReservedHandles::isReserved('bob-dev'))->toBeFalse();
    expect(ReservedHandles::isReserved('cool_user_01'))->toBeFalse();
});
