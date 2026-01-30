# 週報通 Timesheet SaaS：Laravel 12 × React 工作週報系統開發說明

本文件為**系統規格與願景文件**，區分已實作與未來規劃，供開發與產品規劃參考。

### 實作狀態說明

- **✅ 已完成**：功能已上線或可正常使用。
- **🚧 部分完成**：部分流程或 UI 已實作，尚缺後端或整合。
- **📋 未來規劃**：尚未實作，列為後續階段目標。
- **可選**：依需求決定是否實作，非必要功能。

---

## 1. 系統概述（新多租戶架構）
- **系統目標**：提供多租戶的工作週報平台，每間公司於專屬 slug 路徑下獨立運作。以 Laravel 12 + Inertia v2 + React 前端實作，沿用 Laravel Fortify 處理登入、註冊、密碼重設與內建 2FA，並支援 Google OAuth。
- **服務範圍**：鎖定臺灣地區企業使用，採繁體中文介面；國定假日資料計算工時為 📋 未來規劃。
- **租戶模型與入口**
  - `主控台（HQ Portal）`：📋 未來規劃。僅系統管理者可登入，用於建立公司租戶、核發 slug；目前可透過 Seeder 或資料庫建立租戶。
  - `專案主站（Global Landing）`：✅ 已實作。`/` 提供公開產品介紹與示範體驗，可引導訪客登入各自租戶。
  - `公司入口`：✅ 已實作。使用 `https://app.example.com/app/{company_slug}` 作為登入路徑（slug path 模式）；子網域模式為可選，程式碼已支援。
  - `Slug 生命週期`：建立後不可修改；未啟用或凍結的公司 slug 會導向停用頁。
- **主要角色**
  - `公司管理者 (Company Admin)`：從主控台核發，完成 Email 驗證即可啟用，負責管理公司基本資料、租戶階層（單位/小組/部門）、指派角色、匯出報表。
  - `單位主管 (Division Lead)`：負責所屬單位下的組織設定與週報審核，可視需要設定小組。
  - `部門主管 (Department Manager)`：掌握部門報表、完成審核與CSV匯出。
  - `小組長 (Team Lead)`：管理小組成員週報、發起初階審核、協調跨組協作。
  - `一般成員`：在個人所屬部門或小組下建立週報，瀏覽歷史記錄。
  - `自助註冊申請者`：在公司入口視情況開放時註冊，需經 email 驗證並受租戶人數上限限制。
- **核心流程**
  - 多租戶驗證：✅ 租戶 slug 解析 → 檢查租戶狀態 → 套用對應資料庫範圍 → 進入登入流程。
  - 列表：✅ 依週期、報告人、狀態等篩選，僅於租戶邊界內查詢。
  - 週報編輯：✅ 支援複製上一週、拖曳排序（@dnd-kit）、工時統計；Redmine/Jira Issue 查詢為可選（僅欄位儲存，無 API）。
  - 匯總檢視：📋 未來規劃。提供自根節點到葉節點的不同層級報表。
  - 安全管控：✅ Policy 確認租戶 slug 與使用者組織層級，防止越權。
- **前端呈現要點**
  - ✅ 公司入口顯示公司名稱、品牌色與公告；歡迎頁模組化（Hero、QuickSteps、Announcements 等）。
  - ✅ 主站與租戶歡迎頁提供 WelcomeShowcase 示範週報填寫流程。
  - ✅ 全站使用 Inertia + Wayfinder 搭配 slug；表單採 Inertia useForm，可搭配 zod 驗證。
  - ✅ 週報列表與表單已實作；TanStack Table / React Query 為可選增強。
- **導入新專案時的差異化重點**
  - 以 RESTful API + JSON 取代舊版表單流程，Slug 為 API 路由前綴（`/api/v1/{company_slug}/...`），並於中介層驗證租戶。
  - 週報項目拆分為子表 `weekly_report_items`，額外標註層級資訊（單位、部門、小組）以利聚合。
  - 應用 Laravel Sanctum 的 SPA 模式，搭配自訂 middleware 根據 slug 裝載公司設定與品牌化資訊。
  - 前端導入 TypeScript 型別、Permission Guard，以 slug + 角色雙重判斷呈現內容。

## 2. 後端架構設計（Laravel 12）
- **套件與基礎**
  - 使用 `laravel/breeze --react` 作為 Starter Kit，啟用 Email 驗證、密碼重設與兩步驟驗證。
  - 驗證：Laravel Sanctum（SPA 模式）+ Breeze 內建 2FA（基於 Time-Based OTP）。
  - 排程：`php artisan schedule:work` 用於週期性提醒（例如週報填寫通知）。

- **資料庫設計**（建議使用 MySQL）
  - `companies`
    - 欄位：`id`, `name`, `slug`, `status`（active/suspended/onboarding）, `branding` JSON（logo、色票、公告）、`timezone`, `user_limit`（預設 50，可由系統管理者調整）, `current_user_count`, timestamps, soft deletes。
    - 功能：租戶識別，slug 建立後不可變更，狀態控制租戶是否可登入；`user_limit` 決定租戶最大成員數。
  - `company_settings`
    - 儲存租戶偏好設定（週期起迄、是否啟用單位/小組層級、是否開放自助註冊、報表匯出格式、歡迎頁模組配置、IP 限制清單）。
    - `welcome_page` JSON：`hero`（title, subtitle, backgroundImage, ctaLinks[]）、`quickSteps`（最多 5 個步驟）、`demoType`（defaultDemo/videoUrl/customEmbed）、`announcements`、`supportLinks`。
    - `login_ip_whitelist` JSON：最多 5 組 IPv4/IPv6 或 CIDR，預設為空表示全開放。
  - `divisions`（單位）
    - 欄位：`id`, `company_id`, `name`, `code`, `is_active`, `sequence`。
    - 可選：若公司未啟用單位層級則不建立記錄。
  - `departments`
    - 欄位：`id`, `company_id`, `division_id`（nullable）, `name`, `code`, `is_active`, `sequence`。
    - 若未使用單位層級，`division_id` 為 null；仍保留原部門概念供既有流程使用。
  - `teams`（小組）
    - 欄位：`id`, `company_id`, `department_id`, `name`, `code`, `is_active`, `sequence`。
    - 若公司只用到部門層級，則不建立 team。
  - `users`
    - 延伸 Laravel user：`company_id`, `division_id`（nullable）, `department_id`（nullable）, `team_id`（nullable）, `role`（enum：member/team_lead/department_manager/division_lead/company_admin/hq_admin）, `invited_by`, `last_login_at`, `registered_via`（invite/self-service）, `email_verified_at`。
    - `company_admin` 預設於完成 Email 驗證後即啟用，日後如需審核可透過額外狀態欄位擴充。
    - 使用多租戶 constraint 確保外鍵皆屬同一公司，自助註冊需檢查 `user_limit`。
  - `weekly_reports`
    - 欄位：`id`, `company_id`, `division_id`（nullable）, `department_id`, `team_id`（nullable）, `user_id`, `report_date`（週一日期）, `work_year`, `work_week`, `overall_comment`（可選）, `status`（draft/submitted/locked）, `submitted_at`, `approved_at`, timestamps, soft deletes。
    - 新增 `division_id`、`team_id` 以支援層級聚合。
  - `weekly_report_items`
    - 欄位：`id`, `weekly_report_id`, `type`（enum：current/next/support）、`item_title`, `start_date`, `end_date`, `estimated_hours`, `actual_hours`, `owner_id`（可選，用於跨組協作）, `owner_display_name`, `redmine_issue`, `sequence`。
    - 可擴充 `tags` JSON 儲存專案或 KPI。
  - `audit_logs`
    - 欄位：`id`, `company_id`, `actor_id`, `target_type`, `target_id`, `action`, `properties` JSON, `ip`, `user_agent`, timestamps。
  - `role_assignments`（可選）
    - 若需細分權限，可引入多對多表，紀錄使用者於不同層級（公司/單位/部門/小組）的角色。

- **主要模型與關聯**
  - `Company` hasMany `Division`, `Department`, `Team`, `User`, `WeeklyReport`。
  - `Division` belongsTo `Company`; hasMany `Department`, `WeeklyReport`。
  - `Department` belongsTo `Company`、`Division`；hasMany `Team`, `User`, `WeeklyReport`。
  - `Team` belongsTo `Department`、`Company`; hasMany `User`, `WeeklyReport`。
  - `User` belongsTo `Company`，可選 belongsTo `Division`、`Department`、`Team`；hasMany `WeeklyReport`。
  - `WeeklyReport` belongsTo `Company`, `Division`（nullable）, `Department`, `Team`（nullable）, `User`; hasMany `WeeklyReportItem`。
  - Observers：建立週報時自動帶入 `work_year`, `work_week`, `company_id`, `division_id`, `department_id`, `team_id`，並依提交日期判定狀態。

- **Domain / Use Case Layer**
  - 採用 Service/Action pattern：`CreateWeeklyReportAction`, `UpdateWeeklyReportAction`, `SubmitWeeklyReportAction`, `NotifySupervisorAction`, `FetchSummaryAction`（聚合依不同層級）。
  - 引入 `DTO` (Spatie Laravel Data) 或原生 `data object`，包含 `TenantContext` 物件（含 company/division/department/team id），確保每次操作受租戶限制。

- **API 設計**
  - 認證（租戶）：✅
    - 登入／登出、2FA、Google OAuth 經 Fortify／Web 路由處理。
    - ✅ 邀請接受：透過 Web 路由接受邀請並設定密碼；`POST /api/v1/{company_slug}/auth/invitations/accept` 為 📋 預留。
  - HQ 主控台：📋 未來規劃
    - `POST /api/v1/hq/companies`、`PATCH /api/v1/hq/companies/{id}` 等尚未實作。
  - 組織管理（公司入口）：✅
    - `GET /api/v1/{company_slug}/settings` 取得公司設定（組織層級、品牌、歡迎頁、IP 白名單）。
    - ✅ 組織層級彈性設定（Division/Department/Team 可選）、各層級邀請連結生成／啟用／停用。
    - ✅ Division/Department/Team CRUD；`POST /api/v1/{company_slug}/members/invite`、`PATCH .../members/{id}/roles`。
    - ✅ `PUT /api/v1/{company_slug}/settings/ip-whitelist`、`GET/PUT .../welcome-page`。
    - 預留 `POST .../members/{id}/approve`（回傳 404）。
  - 租戶註冊與人數控管：✅
    - 自助註冊、人數上限檢查、邀請連結註冊（依 token 加入對應層級）。
  - 週報 CRUD：✅
    - 列表、建立、編輯、提交、預覽、預填上週；filters 與 pagination 已支援。
    - `DELETE` 軟刪除、`reopen` 為 📋 或已預留。
  - 匯總與報表：📋 未來規劃
    - `GET .../summary/company`、`.../summary/divisions/{id}` 等尚未實作；匯出 CSV/XLSX 同為 📋。
  - 整合服務：
    - Redmine/Jira：可選。目前僅週報項目欄位儲存，無查詢 API。
    - 假期行事曆：📋 `GET .../calendar/holidays` 尚未實作。
  - 標準化：✅ FormRequest 驗證、回傳含組織層級資訊。

- **授權與角色**
  - 角色層級：`hq_admin`（HQ 專用）、`company_admin`、`division_lead`、`department_manager`、`team_lead`（可選）、`member`。
  - `WeeklyReportPolicy`: `viewAny`, `view`, `create`, `update`, `submit`, `reopen`, `delete`, `export`；判斷是否同租戶以及角色是否覆蓋該層級（部門或小組）。
  - `DivisionPolicy`, `DepartmentPolicy`, `TeamPolicy`: 控制層級設定與匯總存取。
  - 中介層 `EnsureTenantScope`：驗證請求 slug、將 `company_id` 設定至 request context；Policy 需同時檢查 slug、層級 ID 與使用者角色。

- **商業邏輯細節**
  - 同週唯一：以 (`company_id`,`user_id`,`work_year`,`work_week`) 建立 unique constraint；若公司啟用小組模式，可允許 `team_id` 替換（支援跨組輪調，以 Policy 決定是否允許）。
  - `PreviousWeekTemplateService`：載入上一週週報時，同步帶出 division/department/team 資訊；若使用者被調動，提供對應提示。
  - 防重提交：使用 Redis/Cache 依 `company_id + user_id` 記錄送出時間（30 秒），並在 API preflight 驗證。
  - 層級聚合：`SummaryAggregator` Service 依據查詢層級（公司/單位/部門/小組）切換資料來源與回傳欄位。
  - 匯出：使用 Laravel `LazyCollection` + `League\Csv`，檔名含 slug 與週別，例如 `{slug}-2025W18-department.csv`。
  - ISO 週別：使用 Carbon `isoWeek`、`isoWeekYear` 計算 `work_year`、`work_week`；注意年初與年末跨年週需同步更新唯一鍵與報表顯示。
  - 週工時計算：依公司設定的時區與假期表計算；假期資料可存於 `holidays` 表或 Redis JSON，支援租戶自訂或同步政府平台。
  - 假日警示：前端在編輯週報時即時計算項目日期是否落在假日或例外工作日，若超出標準工時以顏色標註提醒但不阻擋填寫。
  - 人數上限：建立或邀請新成員前需鎖定 `companies` 列記錄，確保 `current_user_count < user_limit`；若達上限則回傳專用錯誤碼，前端顯示提示並提供聯絡管理者指示。
  - 歡迎頁配置：`WelcomePageConfigService` 驗證模組開關與內容（例如步驟數量、影片連結格式），支援套用 HQ 預設模板或回滾至系統預設，並將配置快取至租戶 namespace。
  - 登入 IP 控管：若租戶設定白名單，Middleware 於登入與 API 請求檢查來源 IP 是否符合，未設定時即視為全開放。

- **通知與提醒**
  - 租戶排程：依 `company_settings` 設定的提醒時間（公司時區）發送；支援不同層級（小組→部門→單位）序列提醒。
  - Laravel Notifications + Queue：每週五提醒成員填寫、週末提醒主管匯整、週一早上寄送匯總給上層主管。
  - 主管通知：成員提交週報時自動寄送 Email 給直屬主管（可選 CC 單位主管），提供摘要與快速連結。
  - 整合 Webhook：Microsoft Teams/Slack/Email，多租戶以 slug 區分頻道；支援自訂訊息模板。

## 3. 前端設計（React + TypeScript）
- **專案結構** ✅
  - 使用 Vite + TypeScript、Inertia v2 + React 19；Wayfinder 產生路由 helper（`@/actions`、`@/routes`）。
  - 主要目錄：`resources/js/pages`、`resources/js/components`（含 `ui/` shadcn 風格、`tenant/` 業務元件）、歡迎頁模組等。

- **路由規劃** ✅（Web 採 `/app/{company:slug}` 前綴）
  - 主站：`/`（Global Landing）、租戶歡迎頁 `/app/{slug}`。
  - 租戶：登入、註冊、2FA、Dashboard、設定、成員、組織、週報列表／表單／預覽、邀請接受與邀請連結註冊。
  - 匯總與 HQ Portal：📋 未來規劃。

- **頁面與元件（已實作）** ✅
  - `WeeklyReportListPage`：列表、工時統計、狀態標記；FiltersPanel 部分實作。
  - `WeeklyReportFormPage`：`CurrentWeekSection` / `NextWeekSection` 拖曳排序（@dnd-kit）、標題／起訖日／工時、`TotalsSummary`、複製上週；Redmine/Jira 為可選（僅欄位）。
  - `GlobalLandingPage`、`TenantWelcomePage`：WelcomeShowcase、Hero、QuickStartSteps、Announcements、SupportContacts 等模組。
  - 租戶設定：歡迎頁、IP 白名單、組織層級設定、各層級邀請連結管理（OrganizationLevelsCard、OrganizationInvitationSection）。
  - 成員管理：成員列表、邀請、角色編輯；邀請連結註冊頁（register-by-invitation）。
  - 設定：個人資料、密碼、外觀、2FA。
  - **📋 未來規劃**：SummaryPages（匯總報表）、HQPortalPages（主控台）、匯出按鈕、假日警示 UI。

- **狀態與資料流程** ✅
  - Inertia 管理頁面與表單狀態；表單採 useForm，可搭配 zod；slug 變動時依 Inertia 重新載入 props。

- **使用者體驗** ✅
  - 固定底部操作欄、智慧變更檢測、詳細錯誤提示、自動捲動至錯誤、toast 提示、繁體中文介面。

## 4. 前後端串接與部署流程
- **開發環境**
  - Backend：`php 8.3`, `composer install`，`.env` 加入多租戶設定：`PRIMARY_DOMAIN`、`TENANT_SLUG_MODE`（subdomain/path）、`SANCTUM_STATEFUL_DOMAINS` 包含主網域與 wildcard 子網域。
  - Frontend：`node 20`, `npm install`，`.env` 配置 `VITE_PRIMARY_DOMAIN`、`VITE_TENANT_STRATEGY`、`VITE_APP_ENV`；開發伺服需支援 slug 子路徑或子網域代理。
  - 建議以 Laravel Sail 或 Docker Compose（PHP-FPM + Nginx + MySQL + Redis）維持一致環境，Nginx 設定 wildcard subdomain 或 rewrite slug path。

- **身份驗證流程（多租戶）** ✅
  1. 使用者訪問 `/app/{company_slug}`（或可選子網域），EnsureTenantScope 驗證 slug → 載入公司設定與品牌。
  2. 登入／註冊經 Fortify Web 路由，搭配 Sanctum CSRF cookie。
  3. 2FA 導向 two-factor-challenge，提交 OTP 後取得 session。
  4. 後續請求經 `auth:sanctum` 與 Policy 檢查租戶與角色。
  5. HQ Portal：📋 未來規劃（獨立域名與 API 尚未實作）。

- **API 版本管理** ✅
  - 租戶 API 採 `Route::prefix('api/v1/{company:slug}')` 搭配 Route Model Binding。
  - HQ API 為 📋 未來規劃。

- **部屬與 CI/CD** 📋 未來規劃
  - Pipeline、staging/production 部署、排程提醒等見 Phase 4 文件。

- **監控與日誌**
  - Laravel log channel 設定到 Stackdriver/CloudWatch。
  - 前端導入 Sentry 捕捉錯誤。
  - 實作 `audit_logs` 搭配 `Monolog` 以追蹤匯出或刪除行為。
  - 日誌需寫入 `company_slug`、`division_id` 等欄位，方便過濾租戶事件。

- **安全與合規**
  - Rate Limiter 依 `company_id` + `user_id` 設定配額，防止單一租戶濫用。
  - 提供資料保留（Retention）設定，支援自動封存或匿名化。
  - 若有跨國資料傳輸需求，記錄租戶時區與資料中心位置，支援 SLA 告示。

## 5. 附錄：測試、擴充與維運
- **測試策略（現況）** ✅
  - 後端：Pest Feature Tests 約 26 個（認證、租戶、組織、週報、設定等），Pest Browser Tests 2 個（週報流程、組織邀請流程）。
  - 執行：`php artisan test --compact`；需 PHP ≥ 8.3。
  - 前端 E2E、合約測試：📋 未來規劃。

- **租戶設定與品牌** ✅
  - `company_settings` 提供 UI：組織層級啟用、歡迎頁模組、登入 IP 白名單；品牌（LOGO、主副色、公告）已支援。
  - 安全設定：2FA 已實作；IP 白名單 UI 已完成，後端 middleware 檢查為 📋。

- **資料品質與維運**
  - 假期同步、排程提醒、匯出報表、審計日誌寫入：📋 未來規劃。
  - 冷資料／封存政策：📋 未來規劃。

- **未來擴充方向**
  - 高優先：假期同步、匯總報表、報表匯出（CSV/XLSX）、IP 白名單 middleware、審計日誌記錄。
  - 中優先：HQ Portal、通知與提醒（週報填寫、主管匯總）。
  - 可選：Redmine/Jira 整合、子網域模式、多語系、LINE/Teams、OKR/KPI 整合。
