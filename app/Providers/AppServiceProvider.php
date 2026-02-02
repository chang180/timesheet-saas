<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->mixedCase()->uncompromised()
                : $rule->uncompromised();
        });

        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api.tenant', function (Request $request) {
            $user = $request->user();
            $company = $request->route('company');

            if (! $user) {
                return Limit::perMinute(60)->by($request->ip());
            }

            $companyId = $company?->id ?? $user->company_id;
            $key = "tenant:{$companyId}:user:{$user->id}";

            return Limit::perMinute(120)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => '請求過於頻繁，請稍後再試。',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });
    }
}
