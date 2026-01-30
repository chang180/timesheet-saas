# Phase 3：整合測試、通知與報表安全強化

> 目標：串接前後端流程、實作通知與報表匯出功能，並完成假期同步、IP 白名單等安全措施，確保系統可於準生產環境穩定運作。

## 📊 完成度：約 25%

**最後更新：** 2026-01-30

**說明**：以下項目已完成（Claude 實作、Cursor 接續補齊）：週報 Reopen 流程、IP 白名單 Middleware（登入與已認證租戶路由皆檢查）、審計日誌（AuditService、IP 白名單/歡迎頁/Reopen 寫入 audit_logs）、相關 Feature 測試。尚未實作：假期同步、匯總報表、報表匯出、通知與提醒、Rate limiting 等。

**差距分析與實作說明**：已撰寫 [phase-3-implementation-guide.md](./phase-3-implementation-guide.md)，列出與現行專案的差距及各待完成項目的具體實作步驟，供其他 AI 或開發者依序實作。

### ✅ Phase 3 已完成項目（Claude + Cursor，2026-01-30）

1. **週報 Reopen 流程**
   - ✅ `WeeklyReportController::reopen()`、POST 路由、Policy 授權；成功後寫入審計（AuditService::reopened）。
   - ✅ 前端 `weekly/form.tsx`：已送出且 `canReopen` 時顯示「重新開啟」按鈕。
   - ✅ Feature 測試：管理者可 reopen、成員不可 reopen 自己的、草稿不可 reopen。

2. **IP 白名單 Middleware**
   - ✅ `App\Http\Middleware\EnsureIpWhitelist`：依 `TenantContext` 讀取 `login_ip_whitelist`，空則放行；使用 `App\Support\IpMatcher` 支援 IPv4/IPv6/CIDR。
   - ✅ 已套用至租戶登入/註冊路由（`tenant.auth`、`register` 等）與已認證租戶路由（`app/{company:slug}` 下所有路由）。
   - ✅ Feature 測試：`tests/Feature/Middleware/IpWhitelistMiddlewareTest.php`（空名單放行、IP/CIDR 比對、IpMatcher 單元）。

3. **審計日誌**
   - ✅ `App\Services\AuditService`：log / created / updated / deleted / exported / submitted / reopened；寫入 `audit_logs`（auditable_type/auditable_id、event、properties、ip、user_agent）。
   - ✅ `AuditLog` 模型：EVENT_* 常數、fillable 含 auditable_type/auditable_id。
   - ✅ IP 白名單更新、歡迎頁更新、週報 Reopen 成功時皆呼叫 AuditService 寫入。

**注意：** Phase 2 的組織層級管理與邀請連結功能已於 2026-01-02 完成，包含：
- ✅ 組織層級彈性設定（公司管理者可決定使用幾層）
- ✅ 各層級專屬邀請連結功能
- ✅ 層級管理者邀請連結管理
- ✅ 透過邀請連結註冊流程

## 1. 前置條件

- ✅ Phase 0-2 已完成，後端 API 可回傳正確資料，前端主要頁面可操作。
- ✅ **組織層級管理功能已完成**（2026-01-02）：
  - 組織層級設定（Division/Department/Team 可選）
  - 各層級邀請連結生成與管理
  - 層級管理者權限控制
  - 公開邀請註冊頁面
- 已具備以下資源：
  - 租戶範例資料（公司、部門、小組、成員、週報）
  - SMTP / Mailhog、Slack/Teams Webhook 測試環境
  - 政府開放資料 不需要 API Key

### 功能優先級（Phase 3 建議）

- **高優先級（應保留）**：假期同步、匯總報表、報表匯出（CSV/XLSX）。
- **中優先級（可延後）**：通知與提醒、IP 白名單 middleware、審計日誌實際記錄。
- **建議移除或標記為可選**：Redmine/Jira 整合（僅欄位儲存，無明確需求可不移除規劃但列為可選）。

## 2. 整合測試策略

1. **後端 Feature / Pest 測試**
   - ⚠️ 認證流程（登入、登出、自助註冊 reaching user limit）
   - ✅ 週報 CRUD + submit/reopen 流程（含 reopen 權限與審計）
   - ⚠️ 匯總 API（公司/單位/部門/小組）含時間範圍
   - ✅ 歡迎頁與 IP 白名單設定（含 IP 白名單 middleware 測試）
   - ⚠️ **組織層級管理與邀請連結功能測試**（待完成）
     - 組織層級設定更新
     - 邀請連結生成、啟用/停用
     - 透過邀請連結註冊
     - 層級管理者權限驗證
2. **前端 E2E 測試（Cypress/Playwright）**
   - ⚠️ 新成員註冊 → Email 驗證（可模擬）→ 首次登入
   - ⚠️ 填寫週報，確認假日警示與總工時計算
   - ⚠️ 匯出報表並驗證檔案內容（可解析 CSV/XLSX）
   - ⚠️ 公司管理者調整歡迎頁與 IP 白名單
   - ⚠️ **組織層級邀請連結流程測試**（待完成）
     - 層級管理者生成邀請連結
     - 透過邀請連結註冊
     - 驗證註冊後自動加入正確層級
3. **整合測試腳本**
   - ⚠️ `tests/integration`：模擬 fetch + React Query (MSW) 檢查 UI 行為

**驗收檢查**
- ⚠️ GitHub Actions / CI 能跑過全部測試。
- ⚠️ 建立測試數據重置腳本（artisan command）。

## 3. 通知與提醒機制

1. **Laravel Notifications**
   - ⚠️ 模組：`WeeklyReportReminder`, `WeeklyReportSubmitted`, `WeeklySummaryDigest`
   - ⚠️ 頻率：
     - 週五：提醒成員填寫週報
     - 週末：提醒主管蒐整
     - 週一上午：寄送公司/單位/Bu 部門匯總摘要
   - ⚠️ 管理者可於 `company_settings` 設定提醒時段及啟用狀態。
   - ✅ **成員邀請通知**（已完成，Phase 2）：`MemberInvitationNotification`
2. **Email 模板**
   - ⚠️ 使用 Markdown Mail templates
   - ⚠️ 包含租戶品牌 LOGO、CTA（查看週報、下載匯總）
3. **Webhook**
   - ⚠️ 支援 Slack / Teams（可選）
   - ⚠️ 提供設定介面（Phase 2 UI 或 Postman）

**驗收檢查**
- ⚠️ 使用 Queue job + fake drivers 驗證通知次數與內容。
- ⚠️ 前端展示已送通知的狀態（例如設定頁標註上次觸發時間）。

## 4. 報表匯出與資料處理

1. ⚠️ API 端支援各層級 `?export=csv|xlsx`，使用 `League\Csv` 與 `Spatie/SimpleExcel`。
2. ⚠️ 檔名格式：`{company_slug}-{level}-{YYYYWW}-{timestamp}.{ext}`
3. ⚠️ 內容欄位：
   - 基本資訊：成員、部門、工時合計、假日工時標記
   - 週報項目：本週/下週事項、Redmine/Jira 編號
4. ⚠️ 大量資料處理：
   - 使用 LazyCollection + chunking
   - Queue 匯出（若匯出範圍 > 1000 筆）
   - 完成後以通知方式提供下載連結（S3 或暫存目錄）

**驗收檢查**
- ⚠️ 手動與自動測試檢查 CSV/XLSX 欄位與排序。
- ⚠️ 匯出期間 API 不阻塞，使用者可收到匯出中提示。

## 5. 假期資料與工時計算

1. ⚠️ 建立 `HolidaySyncService`
   - 主要來源：新北市資料開放平台《政府行政機關辦公日曆表》(2017-2026)\
     `GET https://data.ntpc.gov.tw/api/datasets/308dcd75-6434-45bc-a95f-584da4fed251/csv?page={page}&size={size}`
   - CSV 欄位解析：
     | 欄位 | 說明 | 轉換建議 |
     |------|------|---------|
     | `date` (YYYYMMDD) | 單日日期 | 以 `Carbon::createFromFormat('Ymd', $value)` 轉為 `holiday_date` |
     | `year` | 年度字串 | 驗證是否與 `date` 相符 |
     | `name` | 節慶名稱，週末/補班日可能為空 | 允許 nullable |
     | `isholiday` | `是`/`否` | 轉為 boolean：`是`=>true |
     | `holidaycategory` | 類別（放假/補假/補行上班日/週末） | 轉換為 enum 欄位 |
     | `description` | 詳細說明（含補班/補假敘述） | 儲存於 `note` |
   - 資料匯入流程：
     1. 以 `size=400` 逐年擷取，並在無資料時結束迴圈。
     2. 透過 Laravel Collection 轉換欄位後 upsert 至 `holidays` 表：
        - `holiday_date`, `name`, `is_holiday`, `category`, `note`, `source='ntpc'`
        - 另行計算 `iso_week`、`iso_week_year` 供報表使用。
     3. `isholiday == '否'` 且 `holidaycategory == '補行上班日'` -> 設 `is_workday_override=true`。
   - 更新策略：
     - 平時僅讀取專案內的 JSON 檔（`storage/app/holidays/{year}.json`），該檔僅保留必要欄位：`date`, `name`, `is_holiday`, `category`, `note`, `source`, `iso_week_year`, `iso_week`.
     - 建立 `HolidayCacheService::ensureYearLoaded(year)`：讀取 JSON 後檢查是否涵蓋主要國定假日（如同年 1/1、2/28、清明、端午、中秋、10/10 及關聯補假補班日）。若缺資料才呼叫遠端 API 下載，並產生新的 JSON 與資料庫 upsert。
     - API 成功更新後，重新生成對應 JSON（儲存在本地或 S3）並記錄於 `holiday_sync_logs`。
   - Fake Data：本地開發可使用截取的 CSV 片段製作 seeder，以 `.fixtures/holidays_ntpc.csv` 保存。
2. 提供 `GET /api/v1/{company_slug}/calendar/holidays` 給前端使用
3. 前端在週報表單確認項目日期是否落於假日，標註顏色提醒但不阻擋。

- **驗收檢查**
  - ⚠️ 假期 JSON 對應年份至少涵蓋 1/1、2/28、清明、端午、中秋、10/10 等國定假日；若缺資料可自動觸發 API 更新。
  - ⚠️ 假期 API 有快取（Redis），避免頻繁請求外部服務；Key：`holidays:{year}`，有效期 24 小時。
  - ⚠️ 前端顯示假日顏色符合 UI 指引。
  - ⚠️ 匯入流程可處理補班/補假，並於 `description` 中附帶說明供報表列印。

## 6. 安全控制

1. **IP 白名單 Middleware**
   - ✅ 前端 UI 已完成（Phase 2）：`IPWhitelistForm` 元件
   - ✅ 於登入、已認證租戶請求階段檢查來源 IP 是否在 `login_ip_whitelist`（`EnsureIpWhitelist` + `IpMatcher`）
   - ✅ 若設定為空列表，即視為全開放
   - ✅ 支援 IPv4/IPv6/CIDR；IP 白名單更新時寫入 `audit_logs`
   - ⚠️ 非允許 IP 登入時記錄事件至 audit_logs（可選：middleware 內 reject 時寫入）
2. **Rate Limiting**
   - ⚠️ 依 `company_id + user_id` 控制 API 呼叫頻率
   - ⚠️ 提供 `429` 錯誤訊息與建議
3. **Audit Logging**
   - ✅ 記錄 IP 設定變更、歡迎頁更新、週報 Reopen（`AuditService` + `AuditLog`）
   - ⚠️ 記錄匯出報表（待實作匯出 API 時一併寫入）
   - ⚠️ 前端設定頁顯示近期操作摘要
   - ✅ **組織層級設定變更記錄**（已完成，Phase 2）：透過 `UpdateOrganizationLevelsRequest` 驗證並記錄

**驗收檢查**
- ✅ 嘗試非允許 IP 存取租戶路由時，收到 403 並通過 Feature 測試。
- ✅ IP 白名單/歡迎頁/Reopen 變更皆寫入 `audit_logs`；匯出待實作後寫入並可透過 API 查詢。

## 7. 文件與交付

- ⚠️ 更新 `.ai-dev/laravel_weekly_report_spec.md` 若需要調整細節。
- ⚠️ 完成 `README` 的通知、匯出、假期同步啟用指南。
- ✅ 在 `development/phase-3.md` 標記已完成項目與遇到的阻礙（本文件）。
- ✅ **Phase 3 差距與實作說明**：見 [phase-3-implementation-guide.md](./phase-3-implementation-guide.md)，供其他 AI/開發者依步驟實作未完成項目。
- ⚠️ 建議撰寫外掛說明（設定 Slack/Teams、假期 API key）。
- ✅ **組織層級管理功能文件**（已完成，Phase 2）：
  - 組織層級設定說明
  - 邀請連結使用指南
  - 層級管理者權限說明

## 8. 風險與注意事項

- 政府開放資料 API 若調整格式，需快速更新 parser。
- 通知排程需考慮公司時區（設定於 `companies.timezone`）。
- 匯出檔案若使用臨時目錄，需設置排程清除舊檔。
- 若使用者大量提交週報，需監控 Queue / Horizon。

## 9. Phase 2 完成項目補充（2026-01-02）

### ✅ 組織層級管理與邀請連結功能

已完成以下功能，這些功能原本規劃在 Phase 2 但已提前完成：

1. **組織層級彈性設定**
   - ✅ 公司管理者可在設定頁面選擇要使用的組織層級（Division/Department/Team）
   - ✅ 透過 JSON 陣列儲存啟用的層級
   - ✅ 驗證移除層級時需確保該層級下無資料
   - ✅ 前端 UI：`OrganizationLevelsCard` 元件

2. **各層級專屬邀請連結**
   - ✅ 每個 Division/Department/Team 可生成專屬邀請連結
   - ✅ 邀請連結永久有效，可隨時啟用/停用
   - ✅ 層級管理者可查看、生成、管理自己的邀請連結
   - ✅ 前端 UI：`OrganizationInvitationSection` 元件

3. **邀請連結註冊流程**
   - ✅ 公開註冊頁面：`/app/{company_slug}/register/{token}/{type}`
   - ✅ 透過邀請連結註冊的使用者自動加入對應層級
   - ✅ 支援 Division/Department/Team 三種層級類型
   - ✅ 前端 UI：`register-by-invitation.tsx` 頁面

4. **層級管理者權限**
   - ✅ Division Lead 可管理自己 Division 的邀請連結
   - ✅ Department Manager 可管理自己 Department 的邀請連結
   - ✅ Team Lead 可管理自己 Team 的邀請連結
   - ✅ 層級管理者可邀請成員到自己的層級或下層

5. **後端 API**
   - ✅ `OrganizationLevelsController`：組織層級設定 API
   - ✅ `DivisionInvitationController`、`DepartmentInvitationController`、`TeamInvitationController`：邀請連結管理 API
   - ✅ 更新 `InvitationAcceptController` 支援層級邀請連結註冊
   - ✅ 更新 `MemberInviteController` 允許層級管理者邀請成員

6. **資料庫變更**
   - ✅ `company_settings` 表新增 `organization_levels` JSON 欄位
   - ✅ `divisions`、`departments`、`teams` 表新增 `invitation_token` 和 `invitation_enabled` 欄位

### 📝 待完成項目

以下項目仍需在 Phase 3 完成：

1. **高優先級**
   - 假期同步（HolidaySyncService、holidays 表、API）
   - 匯總報表（公司/單位/部門/小組 API 與前端）
   - 報表匯出（CSV/XLSX）

2. **中優先級**
   - 通知與提醒（週報填寫、主管匯總）
   - ~~IP 白名單 middleware~~（✅ 已完成）
   - ~~審計日誌實際記錄~~（✅ AuditService 已完成；匯出報表時再寫入）

3. **測試**
   - ⚠️ 組織層級管理功能的 Feature Tests
   - ⚠️ 邀請連結流程的 Browser Tests
   - ⚠️ 層級管理者權限驗證測試

4. **文件**
   - ⚠️ 更新 API 文件說明組織層級設定與邀請連結功能
   - ⚠️ 撰寫使用者指南說明如何使用邀請連結

### 建議移除或可選

- **Redmine/Jira 整合**：若無明確需求，可自計畫中移除或標記為可選；目前僅週報項目欄位儲存，無查詢 API。

---

> 完成 Phase 3 後，系統已具備主要運作流程，可進入 Phase 4 進行部署與維運規畫。

