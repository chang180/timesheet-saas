<?php

return [
    'demo_tenant' => [
        'enabled' => env('LANDING_DEMO_TENANT_ENABLED', false),
        'name' => env('LANDING_DEMO_TENANT_NAME'),
        'url' => env('LANDING_DEMO_TENANT_URL'),
        'description' => env('LANDING_DEMO_TENANT_DESCRIPTION'),
    ],
];

