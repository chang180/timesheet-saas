# FRONTEND KNOWLEDGE BASE (resources/js/)

## STRUCTURE

```
resources/js/
├── actions/        # Wayfinder-generated controller action functions (DO NOT EDIT)
├── components/
│   ├── tenant/     # 11 tenant-specific UI components
│   └── ui/         # 30 shadcn-style core components (Button, Card, Calendar, etc.)
├── hooks/          # 6 custom React hooks
├── layouts/        # AppLayout.tsx, AuthLayout.tsx, TenantLayout.tsx
├── lib/            # utils.ts (cn helper), date-utils.ts
├── pages/          # Inertia page components (lowercase directories)
│   ├── auth/       # 9 pages: Login, Register, TwoFactor, etc.
│   ├── landing/    # Marketing and public landing pages
│   ├── settings/   # Profile and company configuration
│   ├── tenant/     # Tenant dashboard and welcome-modules/ (5 files)
│   └── weekly/     # Main feature: form.tsx, list.tsx, preview.tsx
├── routes/         # Wayfinder-generated named route functions (DO NOT EDIT)
├── types/          # Global and feature-specific TypeScript definitions
└── app.tsx         # Inertia entry point and plugin configuration
```

## WHERE TO LOOK

| Task              | Location                        | Notes                                        |
| :---------------- | :------------------------------ | :------------------------------------------- |
| Weekly Report UI  | `pages/weekly/form.tsx`         | 945-line main editor with drag-and-drop      |
| Sortable Rows     | `pages/weekly/components/`      | SortableCurrentWeekRow, SortableNextWeekRow  |
| Auth Pages        | `pages/auth/`                   | Fortify-integrated React components          |
| Base UI           | `components/ui/`                | Check here before creating new components    |
| API Actions       | `actions/`                      | Use generated functions for controller calls |
| Named Routes      | `routes/`                       | Use generated functions for URL generation   |
| Dashboard Modules | `pages/tenant/welcome-modules/` | 5 customizable dashboard widgets             |

## CONVENTIONS

- **Wayfinder:** Import from `@/actions/` or `@/routes/`. NEVER hardcode URLs.
- **Forms:** Use `useForm` from `@inertiajs/react` for state and submission.
- **Drag-and-drop:** Use `@dnd-kit` for sortable lists (see weekly report rows).
- **Dates:** Use `date-fns` and `react-day-picker` v9 for all formatting and pickers.
- **Path Alias:** `@/*` maps to `resources/js/*`.
- **Pages:** Directories under `pages/` must be lowercase (auth, weekly, tenant).
- **Types:** Feature-specific types live in `types/api/` (e.g., `weekly-report.ts`).

## ANTI-PATTERNS

- **No Manual Routes:** Never write `/app/slug/weekly-reports` manually. Use Wayfinder: `import { index } from '@/routes/tenant/weekly-reports'`.
- **No Direct API Calls:** Avoid `fetch` or `axios` directly for controller actions. Use `@/actions/`.
- **No Component Duplication:** Always check `components/ui/` for existing primitives (Button, Input, Dialog).
- **No Auto-gen Edits:** Never modify files in `actions/` or `routes/`. They are overwritten by Wayfinder.
- **No Inline Styles:** Use Tailwind v4 utility classes. Avoid the `style` prop.
