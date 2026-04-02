# PROJECT KNOWLEDGE BASE

**Generated:** 2026-04-02T05:21:13Z
**Commit:** e748793
**Branch:** main

## OVERVIEW

週報通 Timesheet SaaS — multi-tenant weekly report & timesheet platform. Laravel 12 + Inertia v2 + React 19 + Tailwind v4. Multi-tenancy via URL slug (`/app/{company:slug}`), data isolation via `TenantContext` + `BelongsToTenant` trait.

## STRUCTURE

```
.
├── app/                    # Backend: Controllers, Models, Services, Middleware, Policies
│   ├── Http/Controllers/Tenant/  # 20 tenant-scoped controllers (weekly reports, org, settings)
│   ├── Models/             # 10 Eloquent models (Company, WeeklyReport, Division, etc.)
│   ├── Services/           # Business logic (export, holiday sync, auth)
│   ├── Tenancy/            # TenantContext, BelongsToTenant trait
│   └── Neuron/             # AI agent tooling (ToolExecutorAgent) — not core business logic
├── resources/js/           # Frontend: React 19 + Inertia v2 SPA
│   ├── pages/              # Inertia page components (auth, weekly, settings, tenant, landing)
│   ├── components/         # Shared UI (ui/ = shadcn-style, tenant/ = tenant-specific)
│   ├── actions/            # Wayfinder-generated controller action functions
│   ├── routes/             # Wayfinder-generated named route functions
│   └── hooks/              # Custom React hooks
├── routes/                 # web.php (tenant-scoped), api.php, console.php, settings.php
├── config/                 # tenant.php, neuron.php, landing.php (non-standard ones)
├── database/               # 20 migrations, 9 factories, seeders
├── tests/                  # Pest 4: Feature/, Unit/, Browser/
├── .ai-dev/                # AI dev governance, specs, phase docs (NOT source code)
└── .claude/skills/         # Domain-specific skill files for AI agents
```

## WHERE TO LOOK

| Task                   | Location                                                                 | Notes                                                          |
| ---------------------- | ------------------------------------------------------------------------ | -------------------------------------------------------------- |
| Weekly report CRUD     | `app/Http/Controllers/Tenant/WeeklyReportController.php`                 | Main business logic controller                                 |
| Weekly report form UI  | `resources/js/pages/weekly/form.tsx`                                     | Inertia page with drag-and-drop                                |
| Multi-tenancy wiring   | `app/Tenancy/`, `app/Http/Middleware/EnsureTenantScope.php`              | Slug-based, `TenantContext` in container                       |
| Auth flows             | `app/Actions/Fortify/`, `resources/js/pages/auth/`                       | Fortify actions + React auth pages                             |
| Organization hierarchy | `app/Http/Controllers/Tenant/` (`Division`, `Department`, `Team`)        | Company → Division → Department → Team                         |
| Route definitions      | `routes/web.php` (tenant), `routes/api.php` (API), `routes/settings.php` | Tenant routes use `/app/{company:slug}` prefix                 |
| UI components          | `resources/js/components/ui/`                                            | shadcn/ui-style, 30 components                                 |
| Wayfinder routes       | `resources/js/actions/`, `resources/js/routes/`                          | Auto-generated — import from `@/actions/` or `@/routes/`       |
| Middleware             | `app/Http/Middleware/`                                                   | 6 custom: tenant scope, IP whitelist, rate limit, etc.         |
| Policies               | `app/Policies/`                                                          | 5 policies for authorization                                   |
| Notifications          | `app/Notifications/`                                                     | 4 notifications (reminders, digest, invite, submitted)         |
| Scheduled tasks        | `routes/console.php`                                                     | Weekly reminders (Fri 16:00), digest (Mon 09:00), holiday sync |
| CI/CD                  | `.github/workflows/tests.yml`, `.github/workflows/lint.yml`              | Tests + Pint + ESLint on push/PR                               |

## CONVENTIONS

- **Middleware registration**: `bootstrap/app.php` — aliases: `tenant`, `hq`, `ip.whitelist`
- **Validation**: Form Request classes in `app/Http/Requests/Tenant/` — string-based rules
- **Route imports (frontend)**: Use `@/actions/` (controllers) or `@/routes/` (named routes) via Wayfinder
- **Model casts**: Use `casts()` method, not `$casts` property
- **PHP formatting**: `vendor/bin/pint --dirty` before finalizing
- **Frontend formatting**: `npm run format && npm run lint`
- **Indent**: 4 spaces (2 for YAML). Single quotes, semicolons (Prettier)
- **TypeScript**: Strict mode enabled. Path alias `@/*` → `resources/js/*`

## ANTI-PATTERNS (THIS PROJECT)

- **Boot path guardrail**: Modifying `bootstrap/app.php`, middleware, providers, or `composer.json` → MUST run HTTP healthcheck (`https://timesheet-saas.test/` returns 200) before commit
- **No `DB::` facade**: Use `Model::query()` instead
- **No `env()` outside config**: Always `config('key')`
- **No dependency changes without approval**
- **No new base folders without approval**
- **No test deletion without approval**

## COMMANDS

```bash
# Development
composer run dev          # Full stack: server + queue + logs + vite
npm run dev               # Vite dev server only
npm run build             # Production build

# Testing
php artisan test --compact                    # All tests
php artisan test --compact --filter=testName  # Filtered

# Formatting
vendor/bin/pint --dirty   # PHP (Laravel Pint)
npm run format            # Frontend (Prettier)
npm run lint              # Frontend (ESLint)
npm run types             # TypeScript check (tsc --noEmit)

# Artisan
php artisan holidays:sync [year?]             # Sync holidays
php artisan weekly-report:send-reminders      # Test reminders
php artisan weekly-report:send-digest         # Test digest
```

## NOTES

- Served by **Laravel Herd** at `https://timesheet-saas.test/` — no `artisan serve` needed
- `CLAUDE.md` is **auto-generated by Laravel Boost** — do not edit manually
- `.ai-dev/` contains AI governance docs, not source code — read for workflow context only
- Frontend changes require `npm run build` (or `npm run dev` running) to be visible
- Inertia pages live in `resources/js/pages/` (lowercase, per `vite.config.ts`)
- Wayfinder files in `resources/js/actions/` and `resources/js/routes/` are auto-generated — do not edit
