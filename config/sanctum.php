<?php

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return [
    'stateful' => [
        'localhost',
        '127.0.0.1',
        'timesheet-saas.test',
        ...array_values(array_unique(array_filter(
            array_map(
                'trim',
                env('SANCTUM_STATEFUL_DOMAINS') ? explode(',', env('SANCTUM_STATEFUL_DOMAINS')) : []
            ),
            fn ($domain) => $domain !== ''
        ))),
    ],

    'guard' => ['web'],

    'expiration' => env('SANCTUM_EXPIRATION'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'ensure_frontend_requests_are_stateful' => EnsureFrontendRequestsAreStateful::class,
    ],
];
