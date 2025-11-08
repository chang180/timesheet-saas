<?php

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return [
    'stateful' => array_values(array_unique(array_filter(
        array_map(
            static fn (string $domain): string => trim($domain),
            array_merge(
                explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', '')),
                config('tenant.stateful_domains', [])
            )
        )
    ))),

    'guard' => ['web'],

    'expiration' => env('SANCTUM_EXPIRATION'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'ensure_frontend_requests_are_stateful' => EnsureFrontendRequestsAreStateful::class,
    ],
];
