# Phase 0-1：專案啟動與後端基礎建置

> 目標：建立可運行的 Laravel × React monorepo 基礎環境，完成多租戶核心模型、資料庫結構與初始認證流程，確保其他團隊成員能在穩定基底上持續開發。

## 📊 完成度：約 90%

**說明**：認證與租戶基礎、組織與成員 API、歡迎頁與 IP 白名單已完成；HQ Portal 未實作（優先級：中，未來規劃）。

## 0. 前置準備

- **輸入資料**
  - `.env.example` 覆蓋所需環境變數清單（API、前端、HQ Portal）。
  - Laravel Breeze (React) 初始化腳本。
  - 多租戶規格：`company_slug`、租戶專屬入口、HQ Portal 功能。
- **環境需求**
  - PHP 8.3、Composer 2.x
  - Node.js 20.x、npm 10.x
  - MySQL 8.x、Redis 7.x
  - 可使用 Laravel Sail / Docker Compose（建議）

## 1. 專案初始化與目錄結構

1. 使用 Breeze (--react) 建立 Laravel 專案並確認 Sanctum SPA 模式設定。
2. 建立前後端共用設定檔：
   - `config/tenant.php`：儲存 `PRIMARY_DOMAIN`、`TENANT_SLUG_MODE` 等參數。
   - `vite.config.ts` 同步處理 slug 子路徑或子網域代理。
3. 建立 `development/`、`docs/`、`database/seeders/tenant/` 等協作目錄。
4. 初始化 Git Hooks / CI 範本（可參考 Phase 4）。

**驗收檢查**
- Laravel 本地啟動成功，可透過 `/sanctum/csrf-cookie` 取得 CSRF。
- React Dev Server 可順利代理到 Laravel API。

## 2. 多租戶資料庫設計與 Migration

1. 依規格建立下列資料表與索引：
   - `companies`（含 `slug`, `user_limit`, `current_user_count`, `status`, `timezone`, `branding` JSON）
   - `company_settings`（含 `welcome_page`, `login_ip_whitelist` JSON）
   - `divisions`, `departments`, `teams`
   - 擴充 `users` 表欄位（`company_id`, 層級外鍵, `role`, `registered_via`, `email_verified_at`）
   - `weekly_reports`, `weekly_report_items`, `audit_logs`
2. 加入必要的外鍵、唯一索引：
   - `companies.slug` 唯一且不可更新
   - `weekly_reports` (`company_id`,`user_id`,`work_year`,`work_week`) unique
3. 建立 Seeder：
   - HQ 管理者帳號
   - 範例公司（含 divisions/departments/teams）
   - 範例使用者、週報資料

**驗收檢查**
- `php artisan migrate --seed` 無錯。
- `php artisan tinker` 驗證模型關聯與 slug 查詢。

## 3. 後端多租戶核心

### 3.1 中介層與 Context
- ✅ 實作 `EnsureTenantScope` Middleware：
  - 解析 URL 中的 `{company_slug}`，載入公司資料與設定。
  - 檢查租戶狀態（active/suspended），非 active 導向停用頁。
  - 將 `TenantContext` 注入 `app()` 或 Request attribute。
- ✅ 前台 API Route group 改為 `Route::prefix('api/v1/{company:slug}')` 搭配 model binding。

### 3.2 認證流程
- ✅ 啟用 Sanctum SPA 流程，確認 CSRF Cookie 與 `stateful` 網域設定。
- ✅ 使用 Laravel Fortify 處理認證流程（非獨立 API 路由）：
  - 登入：透過 Fortify 的 `authenticateUsing` 回調，自動檢查 `TenantContext` 並限制在同一租戶內。
  - 註冊：透過 `CreateNewUser` Action，自動建立新公司與公司管理者帳號。
  - 兩階段驗證（2FA）、密碼重設由 Fortify 內建處理。
  - ✅ Google OAuth：已整合 Laravel Socialite，支援租戶範圍登入／註冊；本機環境可禁用按鈕。
- ✅ 邀請接受流程：透過 Web 路由（InvitationAcceptController）接受邀請並設定密碼；`POST /api/v1/.../auth/invitations/accept` API 為預留。
- 📋 HQ Portal：未實作，優先級為中。以獨立 Route Prefix `api/v1/hq` 並限制 `hq_admin` 角色為未來規劃；路由已預留。

### 3.3 模型與 Policy
- ✅ 定義模型關聯、Observer（自動填入 `company_id`, `work_year`, `work_week` 等欄位）。
- ✅ 建立 Policy：
  - `WeeklyReportPolicy`（包含 `viewAny`, `view`, `create`, `update`, `submit`, `reopen`, `delete`, `export`）
  - `DivisionPolicy`, `DepartmentPolicy`, `TeamPolicy`
- ✅ 建立 `TenantContext` 類別，提供 `companyId()`, `divisionId()` 等 helper。

**驗收檢查**
- ✅ 以 Postman/Insomnia 針對租戶 API 進行基礎 CRUD 測試，確認 slug 驗證與角色授權。
- ✅ 自助註冊在達到人數上限時回傳專用錯誤碼。

## 4. 組織與歡迎頁設定 API

完成下列 API 端點與測試（可用 Feature Test）：

| 功能 | Method & Path | 說明 | 狀態 |
|------|---------------|------|------|
| 取得租戶設定 | `GET /api/v1/{company_slug}/settings` | 返回品牌、層級、歡迎頁、IP 白名單 | ✅ 已完成 |
| 更新歡迎頁 | `PUT /api/v1/{company_slug}/welcome-page` | 驗證模組 JSON 結構、限制步驟數與影片 URL | ✅ 已完成 |
| 更新 IP 白名單 | `PUT /api/v1/{company_slug}/settings/ip-whitelist` | 最多 5 組，空陣列代表全開放 | ✅ 已完成 |
| 邀請成員 | `POST /api/v1/{company_slug}/members/invite` | 建立成員帳號（隨機密碼）、檢查人數上限 | ✅ 已完成（註：目前僅建立帳號，未發送邀請信） |
| 更新成員角色 | `PATCH /api/v1/{company_slug}/members/{id}/roles` | 限公司管理者操作 | ✅ 已完成 |
| 預留審核 API | `POST /api/v1/{company_slug}/members/{id}/approve` | 目前回傳 404，供未來擴充 | ⚠️ 預留 |

**驗收檢查**
- ✅ 所有 API 均透過 `FormRequest` 驗證。
- ✅ 單元測試涵蓋：歡迎頁 JSON schema、IP 白名單範圍（IPv4/IPv6/CIDR）、人數上限競態。

## 5. 文件與交付

- 更新 `README.md` 或相關文件記錄環境建置步驟。
- 在本 `development/` 目錄補充完成情況與已知問題。
- 建議建立 GitHub Issues / Project 欄位追蹤各功能進度。

### 2025-11-08 更新

- ✅ 完成多租戶資料表、Seeder 與 Sanctum 設定。
- ✅ 實作租戶設定／歡迎頁／IP 白名單／成員邀請與角色調整 API。
- ✅ 新增 `TenantContext`、`EnsureTenantScope`、核心模型與 Policies。
- ✅ 補充 `README.md` 與 `docs/README.md`，新增 `tests/Feature/Tenant/SettingsTest.php` 覆蓋主要驗收案例。
- ⚠️ 目前測試需 PHP ≥ 8.3，若環境為 8.2 會在 `php artisan test` 時遇到 typed constant 解析錯誤。

### 2025-12-13 更新（現況盤點）

- ✅ 認證流程：已透過 Laravel Fortify 實作，支援租戶範圍的登入驗證。
- ✅ 自助註冊：已實作，會自動建立新公司與公司管理者帳號。
- ⚠️ 組織層級管理：Division/Department/Team 的 CRUD API 尚未實作（待補完）。
- ⚠️ 成員管理前端：後端 API 已完成，但前端頁面尚未建立（待補完）。
- ⚠️ 邀請接受流程：`POST /api/v1/{company_slug}/auth/invitations/accept` API 尚未實作（待補完）。

### 後續現況（與 Phase 2 完成後對齊）

- ✅ 組織層級管理：Division/Department/Team CRUD API 與前端已於 2026-01-02 完成；組織層級彈性設定、各層級邀請連結、邀請連結註冊均已實作。
- ✅ 成員管理前端：成員列表、邀請、角色管理、邀請連結註冊頁面均已實作。
- ✅ Google OAuth：已實作並文件化於 README。
- 📋 HQ Portal：維持未實作，列為未來規劃。

## 6. 風險與待確認事項

- **租戶資料隔離**：若未來需切換成多資料庫策略，需在此階段保留封裝層（Repository / Service）。
- **Email 驗證送信**：確保郵件服務可在開發環境模擬（Mailhog/SMTP）。
- **假期資料**：Phase 3 將導入政府開放資料，此階段可先建立假資料或手動 JSON。
- **測試資料清理**：Seeders 需標記範例資料，避免與正式資料混淆。

> 完成以上任務後，即可進入 Phase 2 進行前端開發與 UI/UX 規劃。
