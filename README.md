# 週報通 Timesheet SaaS

多租戶工作週報與工時追蹤平台，提供公司管理者統一管理週報填寫、匯總與報表匯出，並支援租戶自訂歡迎頁與通知設定。

## 核心特色

- 多租戶架構：每間公司對應獨立 `slug` 或子網域，資料權限隔離。
- 組織層級：公司 → 單位 → 部門 → 小組，多角色權限管理。
- 週報體驗：上一週複製、拖曳排序、Redmine/Jira 連動、假日警示。
- 匯總與匯出：依公司／單位／部門／小組下載 CSV / XLSX，支援日期區間。
- 通知與提醒：Email、Webhook 提醒填寫與提交，主管可即時掌握狀態。

## 技術棧

- **後端**：Laravel 12、PHP 8.3、Laravel Breeze（React SPA）
- **前端**：Vite、React 18、TypeScript、React Query、Tailwind CSS
- **認證**：Laravel Sanctum（SPA）、內建 2FA
- **資料**：MySQL 8、Redis 7（快取／Queue）
- **測試**：Pest、React Testing Library、Cypress／Playwright

## 安裝

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## 開發啟動

後端（API）：
```bash
php artisan serve
php artisan queue:work
```

前端（React SPA）：
```bash
npm run dev
```

## 建構

```bash
npm run build
```

## 開發規劃與文件

- 完整系統規格：[`./.ai-dev/laravel_weekly_report_spec.md`](.ai-dev/laravel_weekly_report_spec.md)
- 分階段開發指引：[`./.ai-dev/development/`](.ai-dev/development/)

