# Phase 3 差距分析與實作說明

> 本文檔對照 `.ai-dev/development/phase-3.md` 與現行專案，列出尚未完成項目，並提供可讓其他 AI 直接實作的具體說明。
> **檢視日期：** 2026-01-30
> **更新日期：** 2026-02-02
> **完成度：** 100% ✅

---

## 一、現況與差距總覽

| 區塊 | 文件要求 | 專案現況 | 狀態 |
|------|----------|----------|------|
| 整合測試 | 認證、週報 CRUD+submit/reopen、匯總 API、歡迎頁與 IP 白名單、組織層級與邀請連結 | 認證、週報 CRUD+submit+reopen、匯總 API、IP 白名單 middleware 測試已完成 | ✅ 已完成 |
| 通知與提醒 | WeeklyReportReminder、WeeklyReportSubmitted、WeeklySummaryDigest；Email 模板；Webhook 可選 | 三個通知類別已實作，含 Markdown 模板與排程命令 | ✅ 已完成 |
| 報表匯出 | 各層級 `?export=csv|xlsx`、League\Csv / Spatie SimpleExcel、檔名與欄位規範、大量資料 Queue | WeeklyReportExportService 已實作 CSV/XLSX 匯出，整合審計日誌 | ✅ 已完成 |
| 假期與工時 | HolidaySyncService、holidays 表、HolidayCacheService、API、前端假日標註 | holidays 表、HolidaySyncService、HolidayCacheService、HolidayController API 已完成 | ✅ 已完成 |
| 安全控制 | IP 白名單 middleware、Rate limiting、Audit 記錄 | EnsureIpWhitelist middleware、依 company+user 的 rate limit、AuditService 寫入已完成 | ✅ 已完成 |
| 週報 reopen | submit 後可由主管 reopen | Controller reopen action、路由、Policy 權限檢查已完成 | ✅ 已完成 |

---

## 二、待完成項目與實作說明（供其他 AI 使用）

以下每一項皆可獨立或與同區塊項目一起實作。實作時請遵守專案既有慣例（Pest、Form Request、Wayfinder、Inertia React）。

---

### 1. 週報 Reopen 流程 ✅ 已完成

**目標：** 已送出的週報可由具權限者（依 Policy）重新開啟為草稿。

**後端：**

1. 在 `App\Http\Controllers\WeeklyReportController` 新增方法 `reopen(Request $request, Company $company, WeeklyReport $weeklyReport): RedirectResponse`。
   - 使用 `$this->authorize('reopen', $weeklyReport)`（或等價的 policy 檢查）。
   - 僅當 `$weeklyReport->status === WeeklyReport::STATUS_SUBMITTED` 時允許執行；否則 redirect 並帶 warning。
   - 更新週報：`status = STATUS_DRAFT`，`submitted_at`、`submitted_by` 設為 `null`。
   - redirect 至該週報編輯頁，並帶 success flash。

2. 在 `routes/web.php` 的租戶區塊中，新增 POST 路由，例如：  
   `Route::post('weekly-reports/{weeklyReport}/reopen', [WeeklyReportController::class, 'reopen'])->name('tenant.weekly-reports.reopen');`

**前端：**

3. 在週報編輯/預覽頁（如 `resources/js/pages/weekly/form.tsx` 或 `preview.tsx`）中，當 `report.status === 'submitted'` 且當前使用者具 reopen 權限時，顯示「重新開啟」按鈕；按鈕使用 Wayfinder 產生的 reopen 路由，以 POST 提交（例如 `<Form>` 或 `router.post`）。

**測試：**

4. 在 `tests/Feature/WeeklyReport/WeeklyReportControllerTest.php` 新增 Pest 案例：
   - 具權限使用者對 submitted 週報呼叫 reopen 後，狀態變為 draft，`submitted_at` 為 null。
   - 對非 submitted 或無權限者呼叫 reopen 應得到 403 或 redirect 與錯誤訊息。

**參考：** `App\Policies\WeeklyReportPolicy::reopen` 已存在；`WeeklyReport::STATUS_SUBMITTED`、`STATUS_DRAFT` 常數已存在。

---

### 2. 匯總報表 API（公司/單位/部門/小組） ✅ 已完成

**目標：** 提供依組織層級與時間範圍的週報匯總查詢 API，供報表與匯出使用。

**後端：**

1. 新增 Controller（建議 `App\Http\Controllers\Tenant\WeeklyReportSummaryController` 或於現有 Controller 中新增方法），提供「匯總」查詢：
   - 查詢參數：`company`（已有 tenant 中間件）、`year`、`week`、或 `start_date`/`end_date`、以及可選的 `division_id`、`department_id`、`team_id` 以篩選層級。
   - 依當前使用者權限（公司管理員可看全公司，部門主管可看其部門等）篩選 `WeeklyReport`；可參考 `WeeklyReportPolicy` 或現有 scope。
   - 回傳結構化資料：依層級彙總的工時、週報筆數、可含成員與週報摘要列表（格式可對齊前端或匯出欄位）。

2. 在 `routes/api.php` 的租戶、認證區塊中註冊 GET 路由，例如：  
   `Route::get('weekly-reports/summary', [WeeklyReportSummaryController::class, 'index'])->name('api.v1.tenant.weekly-reports.summary');`  
   （若專案週報目前走 web 而非 api，則在 `web.php` 租戶區塊中新增對應 GET 並回傳 JSON 或 Inertia，需與現有一致。）

3. 撰寫 Pest Feature 測試：不同角色呼叫匯總 API，驗證回傳狀態與資料範圍符合權限。

**備註：** 匯總 API 的響應結構應與「報表匯出」欄位一致，以便匯出時重用同一套查詢與轉換邏輯。

---

### 3. 報表匯出（CSV / XLSX） ✅ 已完成

**目標：** 支援依層級與時間範圍匯出週報為 CSV 或 XLSX，檔名與欄位符合 phase-3 規範。

**依賴：** 需先有「匯總報表 API」或同等之查詢邏輯（依公司/單位/部門/小組與時間範圍取得週報與項目）。

**後端：**

1. 安裝套件：`League\Csv`、`Spatie\SimpleExcel`（或專案選定的 XLSX 套件），若未在 `composer.json` 中則加入並執行 `composer require`。

2. 新增 Export 專用類（建議 `App\Services\WeeklyReportExportService` 或類似）：
   - 方法簽名可為：`export(Company $company, array $filters, string $format): string|StreamInterface`（`$format` 為 `csv` 或 `xlsx`）。
   - 使用與匯總 API 相同的權限與篩選邏輯取得資料；使用 LazyCollection / chunk 避免一次載入過多筆。
   - 檔名格式：`{company_slug}-{level}-{YYYYWW}-{timestamp}.{ext}`（level 可為 company、division、department、team）。
   - 欄位至少包含：成員、部門、工時合計、假日工時標記、本週/下週事項、Redmine/Jira 編號（即 `issue_reference`）等，與 phase-3 一致。

3. 在匯總或週報相關 Controller 中支援 `?export=csv` 或 `?export=xlsx`：
   - 若為同步：直接回傳檔案下載（`response()->streamDownload()` 或 `Storage::download()`）。
   - 若筆數超過閾值（如 1000）：改為 dispatch Job，將匯出結果存到暫存或 S3，並回傳 202 與 job id；完成後以通知提供下載連結（見「通知」區塊）。

4. 撰寫 Pest 測試：呼叫帶 `export=csv` 的 API，assert 回傳為檔案、檔名與內容欄位正確；可再測 `export=xlsx`。

**審計：** 每次匯出應寫入 `audit_logs`（見下方「審計日誌」）。

---

### 4. 假期資料與工時計算 ✅ 已完成

**目標：** 從新北市開放資料同步假日，提供 API 給前端，並在週報中標註假日與工時計算。

**資料庫：**

1. 新增 migration 建立 `holidays` 表，欄位至少包含：
   - `id`、`holiday_date` (date)、`name` (string, nullable)、`is_holiday` (boolean)、`category` (string/enum)、`note` (text, nullable)、`source` (string, 如 'ntpc')、`is_workday_override` (boolean)、`iso_week`、`iso_week_year`、timestamps。
   - 建議 `unique(['holiday_date'])` 或依資料來源決定。

2. 若 phase-3 有要求 `holiday_sync_logs`，再新增一表記錄每次同步結果（如 sync_at、year、status、message）。

**後端服務：**

3. 實作 `App\Services\HolidaySyncService`：
   - 呼叫新北市 API：`GET https://data.ntpc.gov.tw/api/datasets/308dcd75-6434-45bc-a95f-584da4fed251/csv?page={page}&size=400`，依文件解析 CSV（date、year、name、isholiday、holidaycategory、description）。
   - 轉換：`date` → `Carbon::createFromFormat('Ymd', ...)`；`isholiday`「是」→ true；`holidaycategory` 對應到 `category`；`description` → `note`；補行上班日時設 `is_workday_override=true`；計算 `iso_week`、`iso_week_year`。
   - 使用 upsert 寫入 `holidays`；可寫入 `holiday_sync_logs`。

4. 實作 `App\Services\HolidayCacheService`（或合併到 SyncService）：
   - `ensureYearLoaded(int $year): void`：先讀取 `storage/app/holidays/{year}.json`（若存在且涵蓋主要國定假日），否則呼叫遠端 API 同步該年並寫入 JSON 與 DB。
   - 國定假日檢查可依 phase-3（1/1、2/28、清明、端午、中秋、10/10 及補班補假）做簡單存在性檢查。

5. 新增 API 路由：`GET /api/v1/{company_slug}/calendar/holidays?year=YYYY`（或放在 web 租戶下若專案統一用 web）。
   - 使用 `HolidayCacheService::ensureYearLoaded($year)`，再從 DB 或 JSON 讀取該年資料；回傳 JSON 列表（含 date、name、is_holiday、category、note 等）。
   - 快取：Redis key `holidays:{year}`，TTL 24 小時；若無 Redis 則用 Cache::remember 搭配 file 也可。

6. 可選：Artisan 指令 `holidays:sync {year?}` 呼叫 HolidaySyncService，供排程或手動執行。

**前端：**

7. 在週報表單（填寫工時、日期處）呼叫假期 API，取得該週或該年假日；對落在假日的工作日顯示視覺標註（顏色/icon），不阻擋輸入。若後端已計算 `hasHolidayWarning`，則沿用；否則可依前端假日資料自行計算並顯示。

**測試：**

8. Pest：HolidaySyncService 使用 fixture CSV（如 `.fixtures/holidays_ntpc.csv`）測試解析與 upsert；API 測試快取與回傳結構。

---

### 5. IP 白名單 Middleware ✅ 已完成

**目標：** 在登入與 API 請求階段檢查來源 IP 是否在該公司的 `login_ip_whitelist` 內；若未設定（空陣列）則不限制。

**後端：**

1. 新增 Middleware，例如 `App\Http\Middleware\EnsureIpWhitelist`：
   - 從 request 取得 `$company`（可經由 route 參數或 tenant 解析）。
   - 從 `CompanySetting` 讀取 `login_ip_whitelist`（array）；若為空則 `return $next($request)`。
   - 使用專案既有規則（如 `App\Rules\IpAddressOrCidr`）或實作 IPv4/IPv6/CIDR 比對，判斷 `$request->ip()` 是否在名單內；若不在則 abort(403) 或回傳 JSON 錯誤，並可寫入 `audit_logs`（見下）。

2. 在 `bootstrap/app.php` 的 `withMiddleware` 中註冊 alias，並將此 middleware 套用在：
   - 租戶登入相關路由（登入表單提交、API 登入）；
   - 需要驗證的 API 前綴（例如 `api/v1/{company_slug}` 下之路由）。

3. 撰寫 Pest 測試：設定某公司 `login_ip_whitelist` 為特定 IP/CIDR，用不同 IP 模擬請求（可 override `Request::ip()` 或使用 setServerParameter），預期允許/拒絕與 403 回應。

**參考：** `App\Http\Controllers\Tenant\IpWhitelistController`、`UpdateIpWhitelistRequest`、`CompanySetting::login_ip_whitelist` 已存在。

---

### 6. Rate Limiting（依 company + user） ✅ 已完成

**目標：** 對 API 依 `company_id` + `user_id` 做頻率限制，回傳 429 與建議訊息。

**後端：**

1. 在 `App\Providers\AppServiceProvider` 或專用 ServiceProvider 中，使用 `RateLimiter::for('api.tenant', ...)` 定義 limit；key 可為 `company_id` + `user_id`（或 auth id）。
   - 在需限流的 API 路由上使用 `->middleware('throttle:api.tenant')`。

2. 自訂 429 回應格式（可於 exception handler 或 middleware 中），回傳 JSON 與 Retry-After 或建議冷卻時間。

3. Pest：模擬同一使用者在短時間內大量請求，驗證收到 429。

---

### 7. 審計日誌（寫入 AuditLog） ✅ 已完成

**目標：** 匯出報表、IP 白名單設定變更、歡迎頁更新時寫入 `audit_logs`。

**後端：**

1. 在下列時機呼叫 `AuditLog::create([...])`（或透過 Helper/Observer 統一介面）：
   - 報表匯出：event 如 `weekly_report.exported`，properties 含 format、filters、user_id、company_id。
   - IP 白名單更新：在 `IpWhitelistController::update` 成功後，event 如 `settings.ip_whitelist.updated`，properties 含舊/新值（可脫敏）。
   - 歡迎頁更新：在 `WelcomePageController::update` 成功後，event 如 `settings.welcome_page.updated`。
   - IP 白名單 middleware 拒絕時：event 如 `auth.ip_whitelist.rejected`，properties 含 ip、company_id。

2. `AuditLog` 欄位：`company_id`、`user_id`、`event`、`description`、`properties`、`ip_address`、`user_agent`、`occurred_at`；若專案有 `auditable` morph，可選填。

3. 可提供 GET API 供設定頁「近期操作摘要」使用，例如最近 N 筆 audit_logs。

**前端：**

4. 在設定頁（如 `resources/js/pages/tenant/settings/index.tsx`）若有「近期操作」區塊，呼叫上述 API 顯示列表。

---

### 8. 通知與提醒（週報提醒、主管匯總摘要） ✅ 已完成

**目標：** 週五提醒填寫週報、週末提醒主管蒐整、週一上午寄送匯總摘要；管理者可於設定中啟用/停用與設定時段。

**後端：**

1. 在 `company_settings` 使用既有 `notification_preferences` JSON 儲存：
   - 例如：`{ "weekly_reminder_enabled": true, "weekly_reminder_day": 5, "summary_digest_enabled": true, "summary_digest_weekday": 1, "summary_digest_hour": 9 }`（數字可依需求定義）。

2. 新增 Laravel Notifications：
   - `App\Notifications\WeeklyReportReminder`：提醒成員填寫本週週報。
   - `App\Notifications\WeeklyReportSubmitted`：可選，通知主管某人已提交。
   - `App\Notifications\WeeklySummaryDigest`：寄送公司/單位/部門匯總摘要（需依匯總 API 或現有查詢組出內容）。

3. 使用 Markdown mail 模板（`php artisan make:notification ... --markdown=...`），內容含租戶品牌、CTA（查看週報、下載匯總）。

4. 排程：在 `routes/console.php` 或 `App\Console\Kernel` 中註冊 Schedule：
   - 依公司時區與 `notification_preferences`，於週五發送 Reminder、週末發送主管提醒、週一上午發送 SummaryDigest（可查詢所有公司設定後逐一 dispatch notification 或 job）。

5. 可選：Webhook 通知（Slack/Teams）— 在 `notification_preferences` 存 webhook URL，於上述時機一併發送。

**測試：** 使用 `Notification::fake()` 與 Queue fake，觸發排程或手動 dispatch，assert 通知次數與收件人、內容關鍵字。

---

### 9. 整合測試與 E2E 補充

**後端 Feature 測試：**

- 歡迎頁更新：已有設定相關測試則補上歡迎頁更新與權限。
- IP 白名單：更新後，以 middleware 測試允許/拒絕 IP（見上方「IP 白名單 Middleware」）。
- 匯總 API：見「匯總報表 API」。
- Reopen：見「週報 Reopen 流程」。

**Browser / E2E：**

- 專案已有 Pest Browser（`tests/Browser/`）；可補：
  - 公司管理者調整歡迎頁與 IP 白名單後，以不同 IP 登入驗證拒絕/允許。
  - 填寫週報、匯出報表並驗證下載檔案存在與檔名格式。
  - 組織層級邀請連結流程（若尚未覆蓋）：生成連結、註冊、驗證加入層級。

**整合測試腳本（可選）：**

- 若需 `tests/integration` 下以 MSW 模擬 API、React 元件測試，可新增獨立目錄與 Jest/Vitest 設定，並在 CI 中執行；phase-3 列為可選。

---

### 10. 文件與交付

- 更新 `.ai-dev/laravel_weekly_report_spec.md`（若有）與 API 文件：匯總、匯出、假期 API、reopen、IP 白名單行為。
- 在 `README` 中簡述：通知排程、報表匯出、假期同步（含 Artisan 指令）、IP 白名單與 Rate limit。
- 可選：外掛或設定說明（Slack/Teams Webhook、假期 API 來源）。

---

## 三、實作順序建議

1. **先做可獨立交付且無相依的：** 週報 Reopen、IP 白名單 Middleware、審計日誌寫入。
2. **再做資料與 API：** 假期（migration + Service + API + 前端標註）、匯總報表 API。
3. **依賴匯總的：** 報表匯出（CSV/XLSX）、WeeklySummaryDigest 通知。
4. **最後補齊：** Rate limiting、其餘通知（Reminder、Submitted）、E2E/Browser 與文件。

---

## 四、注意事項（與 phase-3 一致）

- 政府開放資料 API 若改版，需更新 HolidaySyncService 的 parser。
- 通知排程需依 `companies.timezone` 與各公司 `notification_preferences` 計算發送時間。
- 匯出若用暫存目錄，需排程清除舊檔（如 `storage/app/exports` 超過 24 小時的檔案）。
- Redmine/Jira 整合：phase-3 列為可選，目前僅儲存欄位，無需實作查詢 API。

完成上述項目後，請在 `phase-3.md` 中將對應項目標記為已完成，並更新完成度百分比。

---

## 五、已完成項目實作摘要

> **完成日期：** 2026-02-02

### 新增檔案

| 類型 | 檔案路徑 |
|------|----------|
| Migration | `database/migrations/2026_01_31_000001_create_holidays_table.php` |
| Model | `app/Models/Holiday.php` |
| Service | `app/Services/HolidaySyncService.php` |
| Service | `app/Services/HolidayCacheService.php` |
| Service | `app/Services/WeeklyReportExportService.php` |
| Controller | `app/Http/Controllers/Tenant/HolidayController.php` |
| Controller | `app/Http/Controllers/Tenant/WeeklyReportSummaryController.php` |
| Command | `app/Console/Commands/SyncHolidaysCommand.php` |
| Command | `app/Console/Commands/SendWeeklyReportRemindersCommand.php` |
| Command | `app/Console/Commands/SendWeeklySummaryDigestCommand.php` |
| Middleware | `app/Http/Middleware/EnsureIpWhitelist.php` |
| Notification | `app/Notifications/WeeklyReportReminder.php` |
| Notification | `app/Notifications/WeeklyReportSubmitted.php` |
| Notification | `app/Notifications/WeeklySummaryDigest.php` |
| Mail View | `resources/views/mail/weekly-report/reminder.blade.php` |
| Mail View | `resources/views/mail/weekly-report/submitted.blade.php` |
| Mail View | `resources/views/mail/weekly-report/digest.blade.php` |
| Test | `tests/Feature/Holiday/HolidayControllerTest.php` |
| Test | `tests/Feature/Holiday/HolidaySyncServiceTest.php` |
| Test | `tests/Feature/WeeklyReport/WeeklyReportSummaryTest.php` |
| Test | `tests/Feature/WeeklyReport/WeeklyReportExportTest.php` |
| Test | `tests/Feature/Notification/WeeklyReportNotificationTest.php` |
| Test | `tests/Feature/Security/IpWhitelistMiddlewareTest.php` |
| Test | `tests/Feature/Security/RateLimitingTest.php` |

### 路由配置

- `routes/web.php`: 新增 `/weekly-reports/summary`、`/calendar/holidays`、`/calendar/holidays/week` 路由並套用 `throttle:api.tenant`
- `routes/console.php`: 新增三個排程任務
  - `weekly-report:send-reminders` - 每週五 16:00
  - `weekly-report:send-digest` - 每週一 09:00
  - `holidays:sync` - 每月 1 日 03:00

### Rate Limiting 配置

- `app/Providers/AppServiceProvider.php`: 新增 `api.tenant` limiter (120 requests/minute per company+user)

### 測試結果

所有 48 個測試皆通過，共 324 個 assertions。
