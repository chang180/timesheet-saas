# Laravel 12 × React 工作週報系統開發說明

## 1. 系統概述（對照現行工作週報功能）
- **系統目標**：提供公司人員建立、檢閱與匯總每週工作內容的平台，支援跨公司、跨部門的統一格式，讓主管與專案管理者掌握進度。新專案將以 Laravel 12 API + React 前端實作，沿用 Laravel Starter Kit（Breeze/Jetstream）帶入登入、註冊、密碼重設與原生 2FA。
- **主要角色**
  - `一般成員`：登入後建立或編輯個人週報，檢視歷史記錄。
  - `部門主管`：檢視部門週報匯整、下載 CSV、追蹤工時總計。
  - `系統管理者`：管理公司與部門資料、設定權限、檢視所有報表。
- **核心流程**（參考 CodeIgniter 舊系統 `Weekly_report` 模組）
  - 列表查詢：依日期區間、公司、部門、報告人等條件篩選資料。
  - 新增／編輯週報：填寫「本週事項」、「下週事項」，支援排序、Redmine 編號帶入與上週資料帶入。
  - 部門檢視：依週別聚合部門所有成員資料，計算總工時並支援 CSV 匯出。
  - 安全管控：僅特定部門成員可存取、重複送出防護（快取）、明細操作紀錄。
- **前端呈現要點**（舊系統以資料表 + Modal 操作）：
  - DataTable 伺服端分頁、Flatpickr 選擇日期、Alpine.js + Sortable.js 管理表格列。
  - 透過 AJAX 呼叫後端 API，操作前顯示 SweetAlert 確認訊息。
- **導入新專案時的差異化重點**
  - 以 RESTful API + JSON 取代原本表單 submit + 加密字串傳遞。
  - 週報項目改建議拆成獨立子表（`weekly_report_items`），利於資料分析與查詢。
  - 以 Laravel 的驗證規則、Policy、Form Request 增強資料安全；利用 Laravel Sanctum 為 React 提供 SPA 認證及 CSRF 保護。
  - 前端以 React Router、React Query（或 RTK Query）管理路由與資料同步，並導入型別檢查（TypeScript）。

## 2. 後端架構設計（Laravel 12）
- **套件與基礎**
  - 使用 `laravel/breeze --react` 作為 Starter Kit，啟用 Email 驗證、密碼重設與兩步驟驗證。
  - 驗證：Laravel Sanctum（SPA 模式）+ Breeze 內建 2FA（基於 Time-Based OTP）。
  - 排程：`php artisan schedule:work` 用於週期性提醒（例如週報填寫通知）。

- **資料庫設計**（建議使用 MySQL）
  - `companies`：公司主檔（名稱、代碼）。
  - `departments`：部門資料（隸屬公司、名稱、代碼、是否啟用）。
  - `users`：延伸 Laravel 預設 user，加上 `company_id`、`department_id`、`role`（enum：member/manager/admin）。
  - `weekly_reports`
    - `id`, `user_id`, `company_id`, `department_id`
    - `report_date`（週一日期）、`work_year`, `work_week`
    - `overall_comment`（可選）、`status`（draft/submitted/locked）
    - `submitted_at`, `approved_at`, timestamps, soft deletes。
  - `weekly_report_items`
    - `id`, `weekly_report_id`, `type`（enum：current/next）
    - `item_title`, `start_date`, `end_date`, `estimated_hours`, `actual_hours`, `owner_name`, `redmine_issue`
    - `sequence`（排序用）。
  - `audit_logs`：紀錄重要操作（建立、更新、匯出、審核）。

- **主要模型與關聯**
  - `Company` hasMany `Department`, `WeeklyReport`。
  - `Department` hasMany `User`, `WeeklyReport`。
  - `User` hasMany `WeeklyReport`；`WeeklyReport` hasMany `WeeklyReportItem`。
  - 使用 Observer 自動帶入 `work_year`、`work_week`、`company_id`、`department_id`。

- **Domain / Use Case Layer**
  - 建議採用 Service/Action pattern，例如 `CreateWeeklyReportAction`、`UpdateWeeklyReportAction`、`FetchDepartmentSummaryAction`，封裝交易邏輯。
  - 引入 `DTO` (Spatie Laravel Data) 或原生 `data object` 來包裝請求資料。

- **API 設計**
  - 認證：`POST /login`, `POST /two-factor-challenge`, `POST /logout`（Breeze 預設）。
  - 週報 CRUD：
    - `GET /api/weekly-reports`（filters：date_from, date_to, company, dept, reporter, status, pagination）。
    - `POST /api/weekly-reports` 建立（payload 含 items[]）。
    - `GET /api/weekly-reports/{id}` 詳細資料。
    - `PUT /api/weekly-reports/{id}` 更新（包含項目 diff 處理）。
    - `DELETE /api/weekly-reports/{id}` 軟刪除（僅作者或管理者）。
  - 部門匯總：
    - `GET /api/departments/{id}/weekly-reports`（query：week_start, week_end, export=csv）。
  - Redmine 介接（可選）：`GET /api/integrations/redmine/issues/{issueNo}`。
  - 設計 `FormRequest` 驗證、`Resource` 轉換器確保欄位一致。

- **授權與角色**
  - `WeeklyReportPolicy`: `viewAny`, `view`, `create`, `update`, `delete`, `export`。
  - `DepartmentPolicy`: 限主管與 admin 匯總/下載。

- **商業邏輯細節**
  - 建立前先檢查同週是否已存在，若存在依需求：阻擋或覆寫草稿。
  - `last_week` 預設帶入上一份資料，可透過 service `PreviousWeekTemplateService`。
  - 防重提交：使用 Redis/Cache 記錄使用者送出時間（30 秒），或以資料庫 unique constraint (`user_id`,`work_year`,`work_week`).
  - 匯出 CSV 使用 Laravel `LazyCollection` + `League\Csv`。
  - 週工時計算：依政府行事曆（可放入 `holidays` 表或 JSON cache），於 Service 重用。

- **通知與提醒**
  - Laravel Notifications：每週五提醒成員填寫週報、每週一寄送部門匯總給主管。
  - 可加上 Microsoft Teams/Slack webhook 以延伸現有行為。

## 3. 前端設計（React + TypeScript）
- **專案結構**
  - 使用 Vite + TypeScript；資料請求建議 React Query。
  - 主要目錄：`src/api`, `src/components`, `src/pages`, `src/routes`, `src/store`（若採 Zustand/Redux 作權限狀態）。

- **路由規劃**
  - `/login`, `/two-factor-challenge`, `/dashboard`。
  - `/weekly-reports`（列表），`/weekly-reports/new`, `/weekly-reports/:id/edit`, `/weekly-reports/:id`（檢視）。
  - `/departments/:id/reports`（部門匯總與匯出），`/reports/export`（整體報表）。

- **頁面與元件**
  - `WeeklyReportListPage`
    - `FiltersPanel`：日期區間（週選擇器）、公司/部門下拉、多選狀態。
    - `WeeklyReportTable`：採用 `TanStack Table` 或 `MUI DataGrid`，支援排序、分頁、批次操作。
  - `WeeklyReportFormPage`
    - `CurrentWeekSection` / `NextWeekSection`：可拖曳排序（React Beautiful DnD），欄位控制（標題、起訖日、預估/實際工時、負責人）。
    - `RedmineLookupDialog`：輸入 issue 編號後呼叫 API 預填欄位。
    - `TotalsSummary`：即時計算工時合計與週工時上限警示。
  - `DepartmentSummaryPage`
    - `SummaryHeader` 顯示部門、週期、工時累計。
    - `ExportButton`：觸發 CSV 下載（串接 API `?export=csv`）。
    - `MemberAccordion`：展開顯示各成員的本週／下週事項。
  - `Shared`
    - `TwoFactorSetup`（若需讓使用者啟用/重設 2FA）。
    - `RoleGuard` 高階元件依權限限制路由。

- **狀態與資料流程**
  - 使用 `useQuery` 搭配 `queryKey` (e.g. `['weeklyReports', filters]`)；表單送出後透過 `useMutation` 重新整理列表。
  - 表單管理採 `react-hook-form`，結合 `zod` schema 驗證（對應後端 FormRequest）。
  - 將日期選擇器抽象成 `WeekPicker`（回傳週一日期與週數）以保持一致。

- **使用者體驗**
  - 失敗提示統一使用 `toast` (react-hot-toast) 或 `MUI Snackbar`。
  - 提供草稿自動儲存（localStorage 或後端 autosave）避免資料遺失。
  - 匯入上一週資料時顯示 diff 或關鍵字標記。

## 4. 前後端串接與部署流程
- **開發環境**
  - Backend：`php 8.3`, `composer install`, `.env` 設定 DB、SANCTUM_STATEFUL_DOMAINS、FRONTEND_URL。
  - Frontend：`node 20`, `npm install`, `.env` 設定 `VITE_API_BASE_URL`、`VITE_APP_ENV`。
  - 透過 Laravel Sail 或 Docker Compose（PHP-FPM + Nginx + MySQL + Redis）維持一致環境。

- **身份驗證流程**
  1. React 於 `/login` 送出帳密至 `POST /login`。
  2. 若帳戶啟用 2FA，將導向 `/two-factor-challenge`，提交 OTP 後獲得 Sanctum session cookie。
  3. 後續 API 皆帶上 `X-XSRF-TOKEN`（由 Sanctum middleware 提供）。

- **API 版本管理**
  - 使用 `Route::prefix('api/v1')`；於 `app/Http/Controllers/Api/V1` 建立命名空間。
  - 對外文件建議使用 Laravel OpenAPI（`scribe` 或 `laravel-swagger`）。

- **部屬與 CI/CD**
  - Pipeline 範例：GitHub Actions
    - `phpstan`, `pint`, `pest` -> `npm run lint`, `npm run test` -> 建置前端 -> 部署到 staging/production。
  - 場景：Laravel 部屬至 Kubernetes 或 EC2，React 打包為靜態檔案交由 CDN/Nginx 服務。
  - 設定計畫性排程（`php artisan schedule:run` via cron）發送提醒。

- **監控與日誌**
  - Laravel log channel 設定到 Stackdriver/CloudWatch。
  - 前端導入 Sentry 捕捉錯誤。
  - 實作 `audit_logs` 搭配 `Monolog` 以追蹤匯出或刪除行為。

## 5. 附錄：測試、擴充與維運
- **測試策略**
  - 後端：Pest/PhpUnit 覆蓋 Service、Policy、API；使用 `laravel/testbench` 建立工作週模擬資料。
  - 前端：React Testing Library 覆蓋表單邏輯；Cypress 進行 E2E（登入 + 建立週報 + 匯出 CSV）。
  - 合約測試：利用 `pact` 或 `schemathesis` 確保前後端契約一致。

- **資料品質與維運**
  - 定期排程檢查重複週報、未完成報表並寄出提醒。
  - 透過資料庫 view 或 Looker Studio 進行報表分析（實際工時 vs 預估工時）。
  - 設計 `archived_weekly_reports` 表或冷資料儲存政策，避免主表膨脹。

- **未來擴充方向**
  - 權限細分：支援跨部門協作或專案維度授權。
  - Redmine/Jira Webhook：自動同步 issue 狀態到週報。
  - 多語系支援、通知渠道擴充（LINE、Teams）。
  - 目標管理（OKR）或 KPI 模組整合，將週報連結到年度目標。

- **導入與移轉建議**
  - 先建立匯入腳本，將舊系統 `edoc_weekly_report` JSON 欄位拆解為 `weekly_report_items`。
  - 逐步導入：先開放新專案給單一部門試用，確認流程後再全面切換。
  - 保留舊系統唯讀入口或資料倉儲，以利稽核。

