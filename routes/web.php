<?php

use App\Http\Controllers\PersonalInvitationController;
use App\Http\Controllers\PersonalWeeklyReportController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\WeeklyReportController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [App\Http\Controllers\LandingController::class, 'global'])
    ->name('home');

// Public profile routes (no auth, rate-limited)
Route::middleware('throttle:public-profile')->group(function (): void {
    Route::get('u/{handle}/{year}/{week}', [PublicProfileController::class, 'showReport'])
        ->where(['year' => '[0-9]{4}', 'week' => '[0-9]{1,2}'])
        ->name('public.profile.report');
    Route::get('u/{handle}', [PublicProfileController::class, 'show'])
        ->name('public.profile.show');
});

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

        if ($user->isPersonal()) {
            return redirect()->route('personal.home');
        }

        $company = $user->company;
        $isManager = in_array($user->role, ['owner', 'admin', 'company_admin'], true);

        if ($isManager && $company->onboarded_at === null) {
            return redirect()->route('tenant.settings', $company);
        }

        return redirect()->route('tenant.weekly-reports', $company);
    })->name('dashboard');

    Route::middleware('personal')->prefix('me')->group(function () {
        Route::get('/', fn () => redirect()->route('personal.weekly-reports'))->name('personal.home');

        Route::get('weekly-reports', [PersonalWeeklyReportController::class, 'index'])->name('personal.weekly-reports');
        Route::get('weekly-reports/create', [PersonalWeeklyReportController::class, 'create'])->name('personal.weekly-reports.create');
        Route::post('weekly-reports', [PersonalWeeklyReportController::class, 'store'])->name('personal.weekly-reports.store');
        Route::get('weekly-reports/{weeklyReport}/edit', [PersonalWeeklyReportController::class, 'edit'])->name('personal.weekly-reports.edit');
        Route::put('weekly-reports/{weeklyReport}', [PersonalWeeklyReportController::class, 'update'])->name('personal.weekly-reports.update');
        Route::post('weekly-reports/{weeklyReport}/submit', [PersonalWeeklyReportController::class, 'submit'])->name('personal.weekly-reports.submit');
        Route::post('weekly-reports/{weeklyReport}/toggle-public', [PersonalWeeklyReportController::class, 'togglePublic'])->name('personal.weekly-reports.toggle-public');
        Route::delete('weekly-reports/{weeklyReport}', [PersonalWeeklyReportController::class, 'destroy'])->name('personal.weekly-reports.destroy');

        Route::get('invitations/accept/{token}', [PersonalInvitationController::class, 'showEmailInvitation'])->name('personal.invitations.accept');
        Route::post('invitations/accept/{token}', [PersonalInvitationController::class, 'acceptEmailInvitation'])->name('personal.invitations.accept.store');
        Route::get('invitations/join/{company:slug}/{token}/{type}', [PersonalInvitationController::class, 'showOrgInvitation'])->name('personal.invitations.join');
        Route::post('invitations/join/{company:slug}/{token}/{type}', [PersonalInvitationController::class, 'acceptOrgInvitation'])->name('personal.invitations.join.store');
    });

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

        if ($user?->isPersonal()) {
            return redirect()->route('personal.home');
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
            Route::get('weekly-reports/summary', [App\Http\Controllers\Tenant\WeeklyReportSummaryController::class, 'index'])
                ->middleware('throttle:api.tenant')
                ->name('tenant.weekly-reports.summary');

            Route::get('settings', [App\Http\Controllers\TenantSettingsController::class, 'index'])->name('tenant.settings');
            Route::patch('settings/welcome-page', [App\Http\Controllers\TenantSettingsController::class, 'updateWelcomePage'])->name('tenant.settings.welcome-page');
            Route::patch('settings/branding', [App\Http\Controllers\TenantSettingsController::class, 'updateBranding'])->name('tenant.settings.branding');
            Route::patch('settings/ip-whitelist', [App\Http\Controllers\TenantSettingsController::class, 'updateIPWhitelist'])->name('tenant.settings.ip-whitelist');
            Route::patch('settings/organization-levels', [App\Http\Controllers\TenantSettingsController::class, 'updateOrganizationLevels'])->name('tenant.settings.organization-levels');
            Route::delete('settings/dissolve', [App\Http\Controllers\TenantSettingsController::class, 'dissolve'])->name('tenant.settings.dissolve');

            Route::get('members', [App\Http\Controllers\Tenant\MemberController::class, 'show'])->name('tenant.members');
            Route::delete('members/{member}', [App\Http\Controllers\Tenant\MemberController::class, 'destroy'])->name('tenant.members.destroy');
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

            // 假期 API（有 rate limiting）
            Route::middleware('throttle:api.tenant')->group(function () {
                Route::get('calendar/holidays', [App\Http\Controllers\Tenant\HolidayController::class, 'index'])->name('tenant.holidays');
                Route::get('calendar/holidays/week', [App\Http\Controllers\Tenant\HolidayController::class, 'week'])->name('tenant.holidays.week');
            });
        });
});

// Global Landing (public)
Route::get('landing', [App\Http\Controllers\LandingController::class, 'global'])->name('landing.global');

require __DIR__.'/settings.php';
