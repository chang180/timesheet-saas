<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Primary Domain
    |--------------------------------------------------------------------------
    |
    | The base domain used to generate tenant-aware URLs. This is typically
    | the apex domain that points to the application (e.g. example.test).
    |
    */
    'primary_domain' => env('PRIMARY_DOMAIN', 'timesheet-saas.test'),

    /*
    |--------------------------------------------------------------------------
    | HQ Portal Domain
    |--------------------------------------------------------------------------
    |
    | The fully-qualified domain name used by the HQ portal. This domain is
    | excluded from tenant routing and is dedicated to global administrators.
    |
    */
    'hq_domain' => env('HQ_PORTAL_DOMAIN', 'hq.timesheet-saas.test'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Slug Mode
    |--------------------------------------------------------------------------
    |
    | Determines how tenant slugs are resolved. Supported values:
    |  - "subdomain": tenants are available via {slug}.primary-domain.
    |  - "path": tenants are available via /t/{slug} path prefixes.
    |
    */
    'slug_mode' => env('TENANT_SLUG_MODE', 'subdomain'),

    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | The API prefix that should be applied before the tenant slug. This keeps
    | the frontend, backend, and generated clients in sync.
    |
    */
    'api_prefix' => env('TENANT_API_PREFIX', 'api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Domains that should receive Sanctum stateful cookies. When using the
    | subdomain strategy be sure to include a wildcard for tenant subdomains.
    |
    */
    'stateful_domains' => array_filter(
        array_map('trim', explode(',', env('TENANT_STATEFUL_DOMAINS', 'localhost,127.0.0.1,timesheet-saas.test')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Registration Flags
    |--------------------------------------------------------------------------
    |
    | Feature toggles that control tenant-level self-service registration
    | flows and invitation onboarding.
    |
    */
    'registration' => [
        'enabled' => (bool) env('TENANT_REGISTRATION_ENABLED', true),
        'requires_email_verification' => (bool) env('TENANT_REGISTRATION_REQUIRES_EMAIL_VERIFICATION', true),
    ],
];
