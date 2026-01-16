<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 自訂登入後的重定向邏輯
        $this->app->instance(LoginResponse::class, new class implements LoginResponse
        {
            public function toResponse($request)
            {
                $user = $request->user();

                if (! $user || ! $user->company) {
                    return redirect(config('fortify.home', '/app'));
                }

                // 檢查是否為租戶登入（從請求中獲取 company_slug）
                $companySlug = $request->input('company_slug');
                if ($companySlug && $user->company && $user->company->slug === $companySlug) {
                    // 租戶登入：重定向到租戶首頁
                    return redirect()->route('tenant.home', $user->company);
                }

                // 一般登入：使用默認重定向邏輯
                return redirect(config('fortify.home', '/app'));
            }
        });

        // 自訂註冊後的重定向邏輯
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse
        {
            public function toResponse($request)
            {
                $user = $request->user();

                if (! $user) {
                    return redirect(config('fortify.home', '/app'));
                }

                // 檢查是否為租戶註冊
                $tenantSlug = $request->session()->pull('tenant_registration_company_slug');
                if ($tenantSlug && $user->company && $user->company->slug === $tenantSlug) {
                    // 租戶註冊：重定向到租戶首頁
                    return redirect()->route('tenant.home', $user->company);
                }

                // 一般註冊：使用默認重定向
                return redirect(config('fortify.home', '/app'));
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = (string) $request->input('email');
            $password = (string) $request->input('password');

            if ($email === '' || $password === '') {
                return null;
            }

            // 檢查是否有租戶上下文（從 middleware 設置）或從請求中獲取 company_slug
            $tenant = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            // 如果沒有租戶上下文，但請求中有 company_slug，則嘗試解析租戶
            if (! $tenant && $request->has('company_slug')) {
                $companySlug = (string) $request->input('company_slug');
                if ($companySlug !== '') {
                    $company = \App\Models\Company::where('slug', $companySlug)
                        ->where('status', 'active')
                        ->first();

                    if ($company) {
                        $tenant = TenantContext::fromCompany($company);
                        app()->instance(TenantContext::class, $tenant);
                    }
                }
            }

            $user = User::query()
                ->where('email', $email)
                ->when(
                    $tenant,
                    fn ($query) => $query->where('company_id', $tenant->companyId())
                )
                ->first();

            // 檢查用戶是否存在且密碼正確
            // 注意：即使用戶已連結 Google 帳號（有 google_id），只要他們有設定密碼，仍然可以使用 email/password 登入
            if (! $user || ! Hash::check($password, $user->password)) {
                return null;
            }

            // 如果指定了租戶，驗證用戶是否屬於該租戶
            if ($tenant && $user->company_id !== $tenant->companyId()) {
                return null;
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            // 將登入意圖存入 session，供 Google OAuth 使用
            $request->session()->put('google_auth_intent', 'login');

            return Inertia::render('auth/login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'canRegister' => Features::enabled(Features::registration()),
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(function (Request $request) {
            // 將一般註冊資訊存入 session，供 Google OAuth 使用
            $request->session()->put('google_auth_intent', 'register');

            return Inertia::render('auth/register');
        });

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            if (app()->bound(TenantContext::class)) {
                $throttleKey = app(TenantContext::class)->slug().'|'.$throttleKey;
            }

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
