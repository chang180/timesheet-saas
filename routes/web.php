<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Landing Pages
    Route::get('welcome', [App\Http\Controllers\LandingController::class, 'tenant'])->name('tenant.welcome');

    // Tenant Settings
    Route::get('settings', [App\Http\Controllers\TenantSettingsController::class, 'index'])->name('tenant.settings');
    Route::patch('settings/welcome-page', [App\Http\Controllers\TenantSettingsController::class, 'updateWelcomePage'])->name('tenant.settings.welcome-page');
    Route::patch('settings/ip-whitelist', [App\Http\Controllers\TenantSettingsController::class, 'updateIPWhitelist'])->name('tenant.settings.ip-whitelist');
});

// Global Landing (public)
Route::get('landing', [App\Http\Controllers\LandingController::class, 'global'])->name('landing.global');

require __DIR__.'/settings.php';
