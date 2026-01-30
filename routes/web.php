<?php

use App\Http\Controllers\WeeklyReportController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [App\Http\Controllers\LandingController::class, 'global'])
    ->name('home');

// Google OAuth 路由（公開）
Route::get('/auth/google', [App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('google.callback');

// 租戶登入/註冊路由（公開，不需要認證；仍檢查 IP 白名單）
Route::prefix('app/{company:slug}')
    ->middleware(['tenant', 'ip.whitelist'])
    ->group(function () {
        Route::get('auth', [App\Http\Controllers\Tenant\TenantAuthController::class, 'show'])->name('tenant.auth');
        // 保留舊的 register 路由以向後兼容
        Route::get('register', [App\Http\Controllers\Tenant\TenantRegisterController::class, 'show'])->name('tenant.register');
        // 組織層級邀請連結註冊（公開）
        Route::get('register/{token}/{type}', [App\Http\Controllers\Tenant\InvitationAcceptController::class, 'registerByInvitation'])->name('tenant.register-by-invitation');
        Route::post('register-by-invitation', [App\Http\Controllers\Tenant\InvitationAcceptController::class, 'storeRegisterByInvitation'])->name('tenant.register-by-invitation.store');
    });

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

        // 檢查是否為租戶註冊後的重定向
        $tenantSlug = $request->session()->pull('tenant_registration_company_slug');
        if ($tenantSlug && $company && $company->slug === $tenantSlug) {
            $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);
            if ($isManager && $company->onboarded_at === null) {
                return redirect()->route('tenant.settings', $company);
            }

            return redirect()->route('tenant.home', $company);
        }

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
        ->middleware(['tenant', 'ip.whitelist'])
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
            Route::post('weekly-reports/{weeklyReport}/reopen', [WeeklyReportController::class, 'reopen'])->name('tenant.weekly-reports.reopen');

            Route::get('settings', [App\Http\Controllers\TenantSettingsController::class, 'index'])->name('tenant.settings');
            Route::patch('settings/welcome-page', [App\Http\Controllers\TenantSettingsController::class, 'updateWelcomePage'])->name('tenant.settings.welcome-page');
            Route::patch('settings/branding', [App\Http\Controllers\TenantSettingsController::class, 'updateBranding'])->name('tenant.settings.branding');
            Route::patch('settings/ip-whitelist', [App\Http\Controllers\TenantSettingsController::class, 'updateIPWhitelist'])->name('tenant.settings.ip-whitelist');

            Route::get('members', [App\Http\Controllers\Tenant\MemberController::class, 'show'])->name('tenant.members');
            Route::get('organization', [App\Http\Controllers\Tenant\OrganizationController::class, 'index'])->name('tenant.organization');
            Route::get('invitations/accept/{token}', [App\Http\Controllers\Tenant\InvitationAcceptController::class, 'show'])->name('tenant.invitations.accept');

            // 組織層級管理（Inertia 路由）
            Route::post('divisions', [App\Http\Controllers\Tenant\DivisionController::class, 'store'])->name('tenant.divisions.store');
            Route::patch('divisions/{division}', [App\Http\Controllers\Tenant\DivisionController::class, 'update'])->name('tenant.divisions.update');
            Route::delete('divisions/{division}', [App\Http\Controllers\Tenant\DivisionController::class, 'destroy'])->name('tenant.divisions.destroy');

            Route::post('departments', [App\Http\Controllers\Tenant\DepartmentController::class, 'store'])->name('tenant.departments.store');
            Route::patch('departments/{department}', [App\Http\Controllers\Tenant\DepartmentController::class, 'update'])->name('tenant.departments.update');
            Route::delete('departments/{department}', [App\Http\Controllers\Tenant\DepartmentController::class, 'destroy'])->name('tenant.departments.destroy');

            Route::post('teams', [App\Http\Controllers\Tenant\TeamController::class, 'store'])->name('tenant.teams.store');
            Route::patch('teams/{team}', [App\Http\Controllers\Tenant\TeamController::class, 'update'])->name('tenant.teams.update');
            Route::delete('teams/{team}', [App\Http\Controllers\Tenant\TeamController::class, 'destroy'])->name('tenant.teams.destroy');
        });
});

// Global Landing (public)
Route::get('landing', [App\Http\Controllers\LandingController::class, 'global'])->name('landing.global');

require __DIR__.'/settings.php';
