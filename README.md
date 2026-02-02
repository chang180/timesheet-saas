# 週報通 Timesheet SaaS

多用戶工作週報與工時追蹤平台，提供公司管理者統一管理週報填寫、匯總與報表匯出，並支援用戶自訂歡迎頁與通知設定。

## 核心特色

- **多租戶架構**：每間公司對應獨立 `slug` 路徑（`/app/{company_slug}`），資料權限隔離。
- **認證與安全**：Laravel Fortify + Sanctum，支援 Google OAuth、雙因素認證（2FA）、邀請系統。
- **組織層級**：公司 → 單位（Division）→ 部門（Department）→ 小組（Team），多角色權限管理；公司管理者可彈性選擇啟用的層級。
- **組織邀請連結**：各層級可生成專屬邀請連結，透過連結註冊自動加入對應層級。
- **週報體驗**：上一週複製、拖曳排序（@dnd-kit）、工時統計、智慧表單驗證與錯誤提示；主管可將已送出週報重新開啟（reopen）。
- **匯總與匯出**：依公司／單位／部門／小組查詢匯總、下載 CSV/XLSX（`/app/{company}/weekly-reports/summary?export=csv|xlsx`），支援日期區間與層級篩選。
- **通知與提醒**：成員邀請通知；週報填寫提醒（週五 16:00）、週一上午匯總摘要；可於 `company_settings.notification_preferences` 設定。
- **假期與工時**：新北市開放資料同步國定假日（`holidays:sync`）；API `GET /app/{company}/calendar/holidays`、`/calendar/holidays/week` 供前端標註假日。
- **安全**：IP 白名單 middleware（登入與租戶請求）、依 company+user 的 Rate limiting（120/min）、審計日誌（IP 白名單／歡迎頁／Reopen／匯出）。
- **未來規劃**：HQ Portal（系統管理者主控台）、Redmine/Jira 整合（可選）。

## 技術棧

- **後端**：Laravel 12、PHP 8.4、Laravel Fortify、Laravel Sanctum、Laravel Socialite、Laravel Wayfinder
- **前端**：Inertia v2、React 19、TypeScript、Vite、Tailwind CSS v4、@tanstack/react-query（選用）
- **認證**：Laravel Fortify + Sanctum（支援 2FA、Google OAuth）
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
- `config/sanctum.php` 合併用戶白名單設定，API 採 cookie-based 認證並需透過 `auth:sanctum`。
- `EnsureTenantScope` middleware 會依 `{company:slug}` 或子網域載入租戶 Context。
- 核心模型：`Company`、`CompanySetting`、`Division`、`Department`、`Team`、`WeeklyReport`、`WeeklyReportItem`、`AuditLog` 與 `TenantContext`。
- 主要路由（Web 採 `/app/{company:slug}` 前綴，API 採 `/api/v1/{company:slug}`）：
    - 設定：`GET /api/v1/{company}/settings`、`PUT /api/v1/{company}/welcome-page`、`PUT /api/v1/{company}/settings/ip-whitelist`
    - 組織：Division/Department/Team CRUD、組織層級設定、各層級邀請連結
    - 成員：`POST /api/v1/{company}/members/invite`、`PATCH /api/v1/{company}/members/{id}/roles`、成員列表
    - 週報：週報 CRUD、提交、重新開啟（reopen）、預覽、預填上週
    - 匯總與匯出：`GET /app/{company}/weekly-reports/summary`（可加 `?export=csv|xlsx`）、`GET /app/{company}/calendar/holidays`、`/calendar/holidays/week`（皆套用 `throttle:api.tenant`）

## 測試

- **後端與 E2E**：Pest Feature Tests（214 個，含認證、週報、匯總、匯出、假期、通知、IP 白名單、Rate limiting）、Pest Browser Tests（租戶流程、週報、邀請連結）。
- **執行**：

```bash
php artisan migrate:fresh --seed
php artisan test --compact
```

> 若環境 PHP 版本低於 8.3，執行測試時可能遇到 `typed class constant` 解析錯誤，需升級 PHP 或調整套件版本。

## Google OAuth 設定

本專案支援 Google OAuth 登入/註冊功能。在本機環境（`APP_ENV=local`）時，Google 登入按鈕將自動禁用並顯示提示訊息。

正式環境需要配置以下環境變數：

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

詳細設定步驟請參考：[`./docs/google-oauth-setup.md`](docs/google-oauth-setup.md)

## 開發規劃與文件

- 完整系統規格：[`./.ai-dev/laravel_weekly_report_spec.md`](.ai-dev/laravel_weekly_report_spec.md)
- 分階段開發指引：[`./.ai-dev/development/`](.ai-dev/development/)

## 通知、匯出與假期同步

- **排程**（`routes/console.php`）：`weekly-report:send-reminders`（週五 16:00）、`weekly-report:send-digest`（週一 09:00）、`holidays:sync`（每月 1 日 03:00）。本機可手動執行：`php artisan weekly-report:send-reminders`、`php artisan weekly-report:send-digest`、`php artisan holidays:sync [year?]`。
- **匯出**：具匯出權限者於週報匯總頁可加 `?export=csv` 或 `?export=xlsx` 下載；每次匯出會寫入 `audit_logs`。
- **IP 白名單**：於租戶設定填寫允許 IP/CIDR，空則不限制；登入與已認證租戶請求皆會檢查，非白名單 IP 會記錄至審計日誌並回傳 403.
- **Rate limiting**：租戶 API（匯總、假期等）依 company+user 限制 120 次/分鐘，超過回傳 429 JSON。

## 更新日誌

### 2026-02-02

- **Phase 3 完成**：假期同步（HolidaySyncService、HolidayCacheService、holidays 表、API）、匯總報表 API、報表匯出（CSV/XLSX）、通知與提醒（WeeklyReportReminder、WeeklyReportSubmitted、WeeklySummaryDigest）、IP 白名單 middleware、審計日誌（含 IP 拒絕與匯出）、Rate limiting（api.tenant）、週報 Reopen 審計記錄與 IP 拒絕寫入 audit。
- **測試**：214 個 Pest 測試通過；Phase 3 驗證報告見 `.ai-dev/development/phase-3-verification-report.md`。

### 2026-01-21

- **週報編輯畫面 UX 大幅優化**
    - **固定底部操作欄**：儲存、發佈、預覽按鈕固定在螢幕底部，隨時可見和操作
    - **新增項目按鈕重新設計**：移到卡片底部，更加醒目且易於點擊
    - **空白狀態優化**：沒有項目時在中央顯示大型新增按鈕
- **完整的表單反饋機制**
    - **成功訊息**：儲存成功時顯示綠色提示（修正了 Flash 訊息無法顯示的問題）
    - **詳細錯誤提示**：驗證失敗時列出所有錯誤欄位，精確標示位置（例如「本週第 1 項 - 標題」）
    - **智慧變更檢測**：只有在實際有修改時才提交表單，避免無效儲存
    - **沒有變更提示**：當內容未修改時顯示提示，3 秒後自動消失
- **自動捲動功能**：表單驗證失敗時自動捲動到錯誤位置，並提供「查看錯誤」按鈕
- **修正 ID 驗證錯誤**：新增項目時不再發送 undefined 的 ID 到後端，避免驗證失敗
- **Flash 訊息中間件修正**：在 `HandleInertiaRequests` 中明確加入 flash 訊息共享
- **已發佈週報狀態提示**：無論是否有 flash 訊息，都會顯示「已發佈可繼續編輯」的狀態提示

### 2026-01-20

- **Google OAuth 本機環境優化**：本機環境自動禁用 Google 登入功能，避免配置問題
- **Tailwind CSS v4 語法改進**：更新所有非標準類別語法以符合 v4 規範
    - `min-h-[100vh]` → `min-h-screen`
    - `aspect-[335/376]` → `aspect-335/376`
- **前端類型定義增強**：在 SharedData 中添加 `appEnv` 環境變數支援
