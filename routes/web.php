<?php

use App\Http\Controllers\WeeklyReportController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [App\Http\Controllers\LandingController::class, 'global'])
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();
        $company = $user?->company;

        if (! $company) {
            return Inertia::render('dashboard');
        }

        $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);

        if ($isManager && $company->onboarded_at === null) {
            return redirect()->route('tenant.settings', $company);
        }

        return redirect()->route('tenant.weekly-reports', $company);
    })->name('dashboard');

    Route::get('app', function (Request $request) {
        $user = $request->user();
        $company = $user?->company;

        if (! $company) {
            return redirect()->route('landing.global');
        }

        $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);

        if ($isManager && $company->onboarded_at === null) {
            return redirect()->route('tenant.settings', $company);
        }

        return redirect()->route('tenant.weekly-reports', $company);
    })->name('app.home');

    Route::prefix('app/{company:slug}')
        ->middleware('tenant')
        ->group(function () {
            Route::get('/', function (Request $request, Company $company) {
                $user = $request->user();
                $isManager = $user && in_array($user->role, ['owner', 'admin', 'company_admin'], true);

                if ($isManager && $company->onboarded_at === null) {
                    return redirect()->route('tenant.settings', $company);
                }

                return redirect()->route('tenant.weekly-reports', $company);
            })->name('tenant.home');

            Route::get('welcome', [App\Http\Controllers\LandingController::class, 'tenant'])->name('tenant.welcome');

            Route::get('weekly-reports', [WeeklyReportController::class, 'index'])->name('tenant.weekly-reports');
            Route::get('weekly-reports/create', [WeeklyReportController::class, 'create'])->name('tenant.weekly-reports.create');
            Route::post('weekly-reports', [WeeklyReportController::class, 'store'])->name('tenant.weekly-reports.store');
            Route::get('weekly-reports/{weeklyReport}/edit', [WeeklyReportController::class, 'edit'])->name('tenant.weekly-reports.edit');
            Route::get('weekly-reports/{weeklyReport}/preview', [WeeklyReportController::class, 'preview'])->name('tenant.weekly-reports.preview');
            Route::put('weekly-reports/{weeklyReport}', [WeeklyReportController::class, 'update'])->name('tenant.weekly-reports.update');
            Route::post('weekly-reports/{weeklyReport}/submit', [WeeklyReportController::class, 'submit'])->name('tenant.weekly-reports.submit');

            Route::get('settings', [App\Http\Controllers\TenantSettingsController::class, 'index'])->name('tenant.settings');
            Route::patch('settings/welcome-page', [App\Http\Controllers\TenantSettingsController::class, 'updateWelcomePage'])->name('tenant.settings.welcome-page');
            Route::patch('settings/branding', [App\Http\Controllers\TenantSettingsController::class, 'updateBranding'])->name('tenant.settings.branding');
            Route::patch('settings/ip-whitelist', [App\Http\Controllers\TenantSettingsController::class, 'updateIPWhitelist'])->name('tenant.settings.ip-whitelist');

            Route::get('members', [App\Http\Controllers\Tenant\MemberController::class, 'show'])->name('tenant.members');
            Route::get('organization', [App\Http\Controllers\Tenant\OrganizationController::class, 'index'])->name('tenant.organization');
            Route::get('invitations/accept/{token}', [App\Http\Controllers\Tenant\InvitationAcceptController::class, 'show'])->name('tenant.invitations.accept');
        });
});

// Global Landing (public)
Route::get('landing', [App\Http\Controllers\LandingController::class, 'global'])->name('landing.global');

require __DIR__.'/settings.php';
