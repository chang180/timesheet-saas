<?php

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [App\Http\Controllers\LandingController::class, 'global'])
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $company = $request->user()?->company;

        if (! $company) {
            return Inertia::render('dashboard');
        }

        return redirect()->route('tenant.weekly-reports', $company);
    })->name('dashboard');

    Route::get('app', function (Request $request) {
        $company = $request->user()?->company;

        if (! $company) {
            return redirect()->route('landing.global');
        }

        return redirect()->route('tenant.weekly-reports', $company);
    })->name('app.home');

    Route::prefix('app/{company:slug}')
        ->middleware('tenant')
        ->group(function () {
            Route::get('/', function (Company $company) {
                return redirect()->route('tenant.weekly-reports', $company);
            })->name('tenant.home');

            Route::get('welcome', [App\Http\Controllers\LandingController::class, 'tenant'])->name('tenant.welcome');

            Route::get('weekly-reports', App\Http\Controllers\WeeklyReportPageController::class)->name('tenant.weekly-reports');

            Route::get('settings', [App\Http\Controllers\TenantSettingsController::class, 'index'])->name('tenant.settings');
            Route::patch('settings/welcome-page', [App\Http\Controllers\TenantSettingsController::class, 'updateWelcomePage'])->name('tenant.settings.welcome-page');
            Route::patch('settings/ip-whitelist', [App\Http\Controllers\TenantSettingsController::class, 'updateIPWhitelist'])->name('tenant.settings.ip-whitelist');
        });
});

// Global Landing (public)
Route::get('landing', [App\Http\Controllers\LandingController::class, 'global'])->name('landing.global');

require __DIR__.'/settings.php';
