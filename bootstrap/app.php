<?php

use App\Http\Middleware\EnsureHqAdmin;
use App\Http\Middleware\EnsureIpWhitelist;
use App\Http\Middleware\EnsureTenantScope;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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

        $middleware->alias([
            'tenant' => EnsureTenantScope::class,
            'hq' => EnsureHqAdmin::class,
            'ip.whitelist' => EnsureIpWhitelist::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 測試環境中禁用 statefulApi 來避免 CSRF 問題
        if (! app()->environment('testing')) {
            // 配置 Sanctum SPA 認證
            $middleware->statefulApi();
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
