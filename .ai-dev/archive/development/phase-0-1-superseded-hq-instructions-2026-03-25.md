# 封存：Phase 0-1「HQ Portal 未完成」指示（已過時）

**封存日期**：2026-03-25  
**理由**：HQ Portal 已於 2026-01-30 實作完成（見現行 `phase-0-1.md` 內「2026-01-30 更新」）。以下原文僅供歷史對照。

---

## 交給 AI 的明確指示（Phase 0-1 未完成工作）

若你（Claude、Codex 或其他 AI）要完成本階段未完成的工作，請**僅**實作以下項目，並**不要**修改文件中已標記 ✅ 的功能或既有租戶／認證流程。

### 1. 唯一未完成項目：HQ Portal（系統管理者主控台）

- **目標**：讓具備 `hq_admin` 角色的使用者能透過專用 API 建立公司租戶、核發 slug、更新公司狀態與人數上限。
- **授權**：僅 `User::role === 'hq_admin'` 可存取；請在 HQ 路由群組加上角色檢查（Middleware 或 Policy），非 hq_admin 回傳 403。
- **路由位置**：`routes/api.php` 內已有 `Route::prefix('v1')->prefix('hq')->middleware(['auth:sanctum'])` 群組（約第 14–19 行），目前為空，請在此群組內註冊以下 API。

### 2. 須實作的 API（規格詳見系統規格文件）

| Method | Path | 說明 |
|--------|------|------|
| `GET` | `/api/v1/hq/companies` | 列出所有公司（id, name, slug, status, current_user_count, user_limit, 等），支援分頁。 |
| `POST` | `/api/v1/hq/companies` | 建立公司：必填 `name`, `slug`（唯一且建立後不可變）、可選 `user_limit`（預設 50）、`branding`、`status`。建立後可選：指定一使用者為公司管理者（company_admin）。 |
| `GET` | `/api/v1/hq/companies/{company}` | 取得單一公司詳情。 |
| `PATCH` | `/api/v1/hq/companies/{company}` | 更新公司：`status`（active/suspended/onboarding）、`user_limit`、`branding` 等；不可修改 `slug`。 |
| `PATCH` | `/api/v1/hq/companies/{company}/user-limit` | 僅更新該公司的 `user_limit`（可選，或併入上列 PATCH）。 |

- **完整規格與欄位**：請對照 **`.ai-dev/laravel_weekly_report_spec.md`** 中「2. 後端架構設計」的「API 設計 － HQ 主控台」與「資料庫設計」的 `companies`、`company_settings` 說明。
- **慣例**：與現有租戶 API 一致：使用 `FormRequest` 驗證、回傳 JSON、錯誤時使用標準 HTTP 狀態碼。

### 3. 程式碼錨點（請依此擴充，勿刪改既有邏輯）

- **路由**：`routes/api.php`，第 14–19 行，`Route::prefix('hq')` 群組。
- **User 角色**：`app/Models/User.php` 已有 `isHqAdmin()`（約第 122 行）與 `allRoles()` 含 `hq_admin`（約第 179–184 行）。
- **HQ 管理者種子**：`database/seeders/Tenant/HqAdminSeeder.php` 會建立 `hq.admin@example.com`、角色 `hq_admin`；可於本機 `php artisan db:seed --class=Tenant\\HqAdminSeeder` 建立測試帳號。
- **Company 模型**：`app/Models/Company.php`；欄位含 `slug`, `user_limit`, `current_user_count`, `status`, `branding` 等，請依既有 migration 與規格書操作。

### 4. 驗收與交付

- 撰寫 **Feature Test**（例如 `tests/Feature/Hq/CompanyManagementTest.php`）：至少覆蓋（1）僅 hq_admin 可存取 HQ 端點、（2）建立公司（含 slug 唯一）、（3）PATCH 更新狀態或 user_limit、（4）非 hq_admin 取得 403。
- 執行 `php artisan test --compact`，確保新舊測試皆通過。
- 完成後請在本文件「後續現況」或「交給 AI 的明確指示」區塊註記 HQ Portal 已完成，並簡述實作範圍（例如：HQ companies CRUD + user-limit，含 Feature Test）。

### 5. 注意事項

- **勿改動**：租戶 API（`Route::prefix('{company:slug})`）、Fortify 認證、EnsureTenantScope、既有 Policy 對租戶的邏輯。
- **HQ 與租戶分離**：HQ API 不依賴 `company_slug`，不經過 `EnsureTenantScope`；僅透過 `auth:sanctum` + hq_admin 角色限制存取。
- 若專案有使用 **Laravel Boost / search-docs**，可善用版本文檔確認 Laravel 12、Sanctum、FormRequest 用法。
