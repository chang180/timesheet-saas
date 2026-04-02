# BACKEND KNOWLEDGE BASE (app/)

## OVERVIEW

Laravel 12 backend with multi-tenancy via TenantContext injection.

## STRUCTURE

```
app/
├── Http/
│   ├── Controllers/Tenant/  # 20 controllers (weekly reports, org, settings)
│   ├── Middleware/          # 6 custom (EnsureTenantScope, CheckIpWhitelist, TenantRateLimiter)
│   └── Requests/Tenant/     # 16 form request classes (string-based rules)
├── Models/                  # 10 models (Company, User, WeeklyReport, Division, etc.)
├── Services/                # 6 services (Export, HolidaySync, HolidayCache, Auth)
├── Tenancy/                 # TenantContext and BelongsToTenant trait
├── Policies/                # 5 policies for tenant-scoped authorization
├── Notifications/           # 4 notifications (reminders, digest, invite, submitted)
└── Neuron/                  # AI agent tooling (ToolExecutorAgent)
```

## WHERE TO LOOK

| Task                | Location                                    | Implementation Details                            |
| ------------------- | ------------------------------------------- | ------------------------------------------------- |
| Multi-tenancy Logic | `app/Tenancy/`                              | `TenantContext` service + `BelongsToTenant` trait |
| Tenant Controllers  | `app/Http/Controllers/Tenant/`              | CRUD logic scoped to `TenantContext`              |
| Input Validation    | `app/Http/Requests/Tenant/`                 | Form Request classes with string-based rules      |
| Authorization       | `app/Policies/`                             | Resource-specific access control                  |
| Business Services   | `app/Services/`                             | Heavy logic (exports, holiday sync, caching)      |
| Tenancy Middleware  | `app/Http/Middleware/EnsureTenantScope.php` | Slug-based tenant resolution and scoping          |

## CONVENTIONS

- **Tenant Scoping**: All tenant models must use `BelongsToTenant` trait. `TenantContext` is injected via container for scoping.
- **Validation**: Use Form Request classes in `app/Http/Requests/Tenant/`. Stick to string-based rules over array-based.
- **Policies**: Every tenant-scoped resource needs a matching Policy for authorization.
- **Service Layer**: Extract business logic from controllers into `app/Services/` for complex operations.
- **Notification Pattern**: Keep notifications in `app/Notifications/` and trigger via `notify()` on models.
- **Middleware**: Registered in `bootstrap/app.php` with aliases: `tenant`, `hq`, `ip.whitelist`.
- **PHP Standards**: Strict type hints and return types for every method. Use PHP 8 constructor promotion.

## ANTI-PATTERNS

- No `DB::` facade usage. Always use `Model::query()`.
- No `env()` calls outside config files. Use `config('key')`.
- No inline comments for logic flow. Use PHPDoc for documentation and clean code for readability.
- No direct `Request` input in controllers. Always use Form Requests for validation.
- No manual tenant filtering in queries. Rely on `BelongsToTenant` global scope.
