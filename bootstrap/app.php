<?php

use App\Http\Middleware\EnsureHqAdmin;
use App\Http\Middleware\EnsureIpWhitelist;
use App\Http\Middleware\EnsureTenantScope;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inspector\Laravel\Middleware\WebRequestMonitoring;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', WebRequestMonitoring::class)
            ->appendToGroup('api', WebRequestMonitoring::class);
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Replace framework's CSRF middleware with our custom one
        $middleware->replace(ValidateCsrfToken::class, VerifyCsrfToken::class);

        $middleware->alias([
            'tenant' => EnsureTenantScope::class,
            'hq' => EnsureHqAdmin::class,
            'ip.whitelist' => EnsureIpWhitelist::class,
        ]);

        $middleware->web(
            append: [
                HandleAppearance::class,
                HandleInertiaRequests::class,
                AddLinkHeadersForPreloadedAssets::class,
            ],
            remove: [
                ValidateCsrfToken::class,
            ],
        );

        /**
         * Configure Sanctum SPA authentication.
         *
         * Note: CSRF bypass for testing environments is handled in
         * VerifyCsrfToken::isTestingEnvironment().
         */
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
