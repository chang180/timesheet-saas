# 週報通 Timesheet SaaS

多租戶工作週報與工時追蹤平台，提供公司管理者統一管理週報填寫、匯總與報表匯出，並支援租戶自訂歡迎頁與通知設定。

## 核心特色

- 多租戶架構：每間公司對應獨立 `slug` 或子網域，資料權限隔離。
- 組織層級：公司 → 單位 → 部門 → 小組，多角色權限管理。
- 週報體驗：上一週複製、拖曳排序、Redmine/Jira 連動、假日警示。
- 匯總與匯出：依公司／單位／部門／小組下載 CSV / XLSX，支援日期區間。
- 通知與提醒：Email、Webhook 提醒填寫與提交，主管可即時掌握狀態。

## 技術棧

- **後端**：Laravel 12、PHP 8.4、Laravel Fortify、Laravel Sanctum、Laravel Wayfinder
- **前端**：Inertia v2、React 19、TypeScript、Vite、Tailwind CSS v4、@tanstack/react-query（選用）
- **認證**：Laravel Fortify + Sanctum（支援 2FA）
- **資料**：MySQL 8、Redis 7（快取／Queue）
- **測試**：Pest、Pest Browser、React Testing Library、Playwright

## 安裝

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

## 開發啟動

後端（API）：
```bash
php artisan serve
php artisan queue:work
```

前端（Inertia + React）：
```bash
npm run dev
```

## 建構

```bash
npm run build
```

## 多租戶後端基礎

- 新增 `config/tenant.php` 管理主網域、slug 模式與 Sanctum stateful domains。
- `config/sanctum.php` 合併租戶白名單設定，API 採 cookie-based 認證並需透過 `auth:sanctum`。
- `EnsureTenantScope` middleware 會依 `{company:slug}` 或子網域載入租戶 Context。
- 核心模型：`Company`、`CompanySetting`、`Division`、`Department`、`Team`、`WeeklyReport`、`AuditLog` 與 `TenantContext`。
- 路由：
  - `GET /api/v1/{company}/settings`
  - `PUT /api/v1/{company}/welcome-page`
  - `PUT /api/v1/{company}/settings/ip-whitelist`
  - `POST /api/v1/{company}/members/invite`
  - `PATCH /api/v1/{company}/members/{id}/roles`
  - `POST /api/v1/{company}/members/{id}/approve`（暫回 404，預留未來審核流程）

## 測試

```
php artisan migrate:fresh --seed
php artisan test tests/Feature/Tenant/SettingsTest.php
```

> 若環境 PHP 版本低於 8.3，執行測試時可能遇到 `typed class constant` 解析錯誤，需升級 PHP 或調整套件版本。

## 開發規劃與文件

- 完整系統規格：[`./.ai-dev/laravel_weekly_report_spec.md`](.ai-dev/laravel_weekly_report_spec.md)
- 分階段開發指引：[`./.ai-dev/development/`](.ai-dev/development/)

