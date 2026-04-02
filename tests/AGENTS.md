# TEST SUITE KNOWLEDGE BASE (PEST 4)

## OVERVIEW

Pest 4 test suite for timesheet-saas. Feature-heavy architecture using in-memory SQLite and Pest Browser.

## STRUCTURE

```
tests/
├── Feature/            # 33 files total
│   ├── Auth/           # 8 files: login, register, password, etc.
│   ├── Tenant/         # 9 files: company, division, department, team
│   ├── WeeklyReport/   # 3 files: CRUD, submission, export
│   ├── Settings/       # 3 files: profile, security, company
│   └── AgenticTeam/    # 3 files: AI tool execution logic
├── Unit/               # Isolated logic tests (services, helpers)
├── Browser/            # 3 files: Pest Browser E2E tests
├── Pest.php            # Global uses(), custom expectations, and helpers
└── TestCase.php        # Base Laravel TestCase (disables CSRF, Sanctum)
```

## WHERE TO LOOK

| Task             | Location                      | Notes                                              |
| :--------------- | :---------------------------- | :------------------------------------------------- |
| Auth logic       | `tests/Feature/Auth/`         | Fortify actions and authentication flows           |
| Tenant isolation | `tests/Feature/Tenant/`       | TenantContext and BelongsToTenant trait validation |
| Weekly reports   | `tests/Feature/WeeklyReport/` | Report CRUD, state transitions, and reminders      |
| E2E workflows    | `tests/Browser/`              | Navigation, form filling, and UI assertions        |
| Tool execution   | `tests/Feature/AgenticTeam/`  | Neuron AI agent tool execution logic               |

## CONVENTIONS

- **Pest Syntax**: Use `it()` or `test()` for definitions; `expect()` for assertions; `uses()` for traits.
- **Factories**: Always use `database/factories/` (9 models: Company, User, WeeklyReport, etc.). Check factory states before manual setup.
- **TestCase**: Extends `Illuminate\Foundation\Testing\TestCase`. Disables CSRF/Sanctum middleware by default.
- **Feature vs Unit**: Primary focus on Feature tests. Unit tests for pure logic without DB/HTTP dependency.
- **Browser Testing**: Use Pest Browser (NOT Dusk). APIs: `visit()`, `fill()`, `click()`, `assertSee()`.
- **Naming**: CamelCase file names. Descriptive test names in English or Chinese.
- **PHPUnit Config**: SQLite (`:memory:`), `array` cache, `file` sessions (required for browser tests).
- **Faker**: Use `fake()` helper for all synthetic data generation.

## ANTI-PATTERNS

- **No Test Deletion**: Never delete existing tests without explicit approval.
- **No Coverage Gaps**: New features or bug fixes must include corresponding tests.
- **No DB Facade**: Prefer Model queries or factories over direct `DB::` usage in tests.
- **No Manual Tenant Setup**: Use `TenantContext` helpers defined in `Pest.php`.
