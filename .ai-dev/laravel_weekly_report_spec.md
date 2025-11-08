# Laravel 12 × React 工作週報系統開發說明

## 1. 系統概述（新多租戶架構）
- **系統目標**：提供多租戶的工作週報平台，每間公司於專屬子網域或 slug 路徑下獨立運作。新專案以 Laravel 12 API + React 前端實作，沿用 Laravel Breeze（React 版）處理登入、註冊、密碼重設與內建 2FA。
- **服務範圍**：鎖定臺灣地區企業使用，採繁體中文介面，統一以國定假日資料計算工時。
- **租戶模型與入口**
  - `主控台（HQ Portal）`：僅系統管理者可登入，用於建立公司租戶、核發公司管理者帳號、指派不可變更的 `company_slug`。
  - `專案主站（Global Landing）`：`https://app.example.com/` 提供公開的產品介紹與示範體驗，可引導訪客申請試用或登入各自租戶。
  - `公司入口`：使用 `https://app.example.com/{company_slug}` 或對應公司專屬子網域 (`https://{company_slug}.example.com`) 作為唯一登入路徑。僅該公司成員與受邀合作人員可進入，符合租戶人數上限。
  - `Slug 生命週期`：建立後不可修改，透過系統管理者在主控台建立；未啟用或凍結的公司 slug 會導向停用頁。
- **主要角色**
  - `公司管理者 (Company Admin)`：從主控台核發，完成 Email 驗證即可啟用，負責管理公司基本資料、租戶階層（單位/小組/部門）、指派角色、匯出報表。
  - `單位主管 (Division Lead)`：負責所屬單位下的組織設定與週報審核，可視需要設定小組。
  - `部門主管 (Department Manager)`：掌握部門報表、完成審核與CSV匯出。
  - `小組長 (Team Lead)`：管理小組成員週報、發起初階審核、協調跨組協作。
  - `一般成員`：在個人所屬部門或小組下建立週報，瀏覽歷史記錄。
  - `自助註冊申請者`：在公司入口視情況開放時註冊，需經 email 驗證並受租戶人數上限限制。
- **核心流程**
  - 多租戶驗證：租戶 slug 解析 → 檢查租戶狀態 → 套用對應資料庫範圍（同 DB 但以公司外鍵隔離）→ 進入登入流程。
  - 列表：依週期、單位、部門、小組、報告人、狀態進行篩選，僅於租戶邊界內查詢。
  - 週報編輯：支援複製上一週、拖曳排序、與 Redmine/Jira Issue 查詢；強制維持於租戶資料範圍。
  - 匯總檢視：提供自根節點（公司）到葉節點（小組）的不同層級報表，依角色可見。
  - 安全管控：政策權限（Policy）確認租戶 slug 與使用者組織層級，防止越權。
- **前端呈現要點**
  - 公司入口需顯示公司名稱、品牌色與公告（由公司管理者自訂），以加強租戶識別。
  - 歡迎頁導覽：首頁需提供互動式週報輸入展示（影片/引導式表單 Demo），讓新使用者立即了解填寫流程。
  - 全站使用 React Router 搭配 slug 動態載入；React Query 管理資料快取，根據 slug 變動重設 QueryKey。
  - 週報列表採 TanStack Table + 伺服端分頁，表單採 React Hook Form + Zod。
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
  - 認證（租戶）：
    - `POST /api/v1/{company_slug}/auth/login`、`POST /api/v1/{company_slug}/auth/two-factor-challenge`、`POST /api/v1/{company_slug}/auth/logout`。
    - `POST /api/v1/{company_slug}/auth/invitations/accept` 接受邀請並設定密碼。
  - HQ 主控台：
    - `POST /api/v1/hq/companies` 建立公司、核發 slug，可指定初始歡迎頁模板與品牌設定。
    - `PATCH /api/v1/hq/companies/{id}` 更新公司狀態（activate/suspend）、重設公司管理者、調整歡迎頁預設模板。
  - 組織管理（公司入口）
    - `GET /api/v1/{company_slug}/settings` 取得公司設定（是否啟用單位/小組、品牌、週期規則）。
  - 租戶註冊與人數控管：
    - `POST /api/v1/{company_slug}/auth/register`：租戶入口自助註冊，需確認租戶是否開放且 `current_user_count < user_limit`，完成 Email 驗證後自動啟用。
    - `GET /api/v1/{company_slug}/auth/register/availability`：回傳是否仍開放註冊、人數剩餘額度。
    - 系統管理者可於 HQ Portal 透過 `PATCH /api/v1/hq/companies/{id}/user-limit` 調整人數上限。
    - 預留 `POST /api/v1/{company_slug}/members/{id}/approve` 介面，未啟用時回傳 404，用於未來若需人工審核時啟用。
    - `POST /api/v1/{company_slug}/divisions` / `PATCH /{id}` / `DELETE /{id}`。
    - `POST /api/v1/{company_slug}/departments`（可指定 `division_id`）/ `PATCH` / `DELETE`。
    - `POST /api/v1/{company_slug}/teams`（隸屬部門）/ `PATCH` / `DELETE`。
    - `POST /api/v1/{company_slug}/members/invite`：邀請成員並指定層級與角色。
    - `PATCH /api/v1/{company_slug}/members/{id}/roles`：調整角色與層級指派。
    - `PUT /api/v1/{company_slug}/settings/ip-whitelist`：更新最多 5 組登入 IP 白名單（空陣列代表全開放）。
    - `GET /api/v1/{company_slug}/welcome-page` / `PUT /api/v1/{company_slug}/welcome-page`：取得與更新租戶歡迎頁模組配置（僅公司管理者可操作）。
  - 週報 CRUD：
    - `GET /api/v1/{company_slug}/weekly-reports`（filters：week_start, week_end, division_id, department_id, team_id, reporter_id, keyword, status, pagination, includeDrafts）。
    - `POST /api/v1/{company_slug}/weekly-reports` 建立（payload 包含 items[]、層級資訊）。
    - `GET /api/v1/{company_slug}/weekly-reports/{id}`。
    - `PUT /api/v1/{company_slug}/weekly-reports/{id}`（支援 items diff、草稿更新）。
    - `DELETE /api/v1/{company_slug}/weekly-reports/{id}` 軟刪除（作者或有管理權者）。
    - `POST /api/v1/{company_slug}/weekly-reports/{id}/submit`（提交後鎖定，可由作者或有權角色重新開啟編輯）。
    - `POST /api/v1/{company_slug}/weekly-reports/{id}/reopen`：在需要修改時解除鎖定。
  - 匯總與報表：
    - `GET /api/v1/{company_slug}/summary/company`（參數：date_from, date_to, aggregation=division|department|team, export?）。
    - `GET /api/v1/{company_slug}/summary/divisions/{division_id}`（支援 `date_from`, `date_to`, `export=csv|xlsx`）。
    - `GET /api/v1/{company_slug}/summary/departments/{department_id}`（支援時間範圍與下載格式）。
    - `GET /api/v1/{company_slug}/summary/teams/{team_id}`（支援時間範圍與下載格式）。
  - 整合服務：
    - Redmine/Jira 查詢：`GET /api/v1/{company_slug}/integrations/redmine/issues/{issueNo}`（依租戶設定決定是否啟用）。
    - 假期行事曆：`GET /api/v1/{company_slug}/calendar/holidays`。
  - 標準化：每條 API 透過 `FormRequest` 驗證、`Resource` 轉換器回傳統一欄位，並在 JSON 包含 `division`, `department`, `team` 物件。

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
- **專案結構**
  - 使用 Vite + TypeScript；資料請求建議 React Query。
  - 主要目錄：`src/api`, `src/components`, `src/pages`, `src/routes`, `src/store`（若採 Zustand/Redux 作權限狀態），`src/tenant`（存放 slug 設定與品牌樣式）。

- **路由規劃**
  - 主站：`/`（公開介紹頁，提供引導與共用展示）、`/demo`（可選互動示範）。
  - 租戶入口：`/:companySlug`（租戶歡迎頁，含展示與公告）、`/:companySlug/login`, `/:companySlug/register`, `/:companySlug/two-factor-challenge`, `/:companySlug/dashboard`。
  - 週報：`/:companySlug/weekly-reports`, `/new`, `/:id/edit`, `/:id`。
  - 匯總：`/:companySlug/reports/company`, `/divisions/:divisionId`, `/departments/:departmentId`, `/teams/:teamId`。
  - 主控台（HQ）：`/hq/login`, `/hq/companies`, `/hq/companies/:id`.

- **頁面與元件**
  - `WeeklyReportListPage`
    - `FiltersPanel`：週期選擇器、單位/部門/小組階層篩選、關鍵字（Redmine 編號/標題）、狀態多選。
    - `WeeklyReportTable`：採用 `TanStack Table` 或 `MUI DataGrid`，支援排序、分頁、批次操作，表頭顯示層級標籤。
  - `WeeklyReportFormPage`
    - `CurrentWeekSection` / `NextWeekSection`：可拖曳排序（React Beautiful DnD），欄位控制（標題、起訖日、預估/實際工時、負責人）。
    - `RedmineLookupDialog`：輸入 issue 編號後呼叫 API 預填欄位。
    - `TotalsSummary`：即時計算工時合計與週工時上限警示。
  - `GlobalLandingPage`
    - 展示產品價值、整體流程示意、導向試用或登入租戶。
    - 嵌入共用 `WelcomeShowcase` Demo，以匿名資料展示週報操作。
    - 提供簡要步驟說明，引導新租戶首次登入後應完成的設定（品牌、層級、提醒）。
  - `SelfRegistrationPage`
    - 顯示公司品牌、剩餘可註冊人數、是否需要管理者二次審核。
    - 表單欄位：姓名、Email、部門/小組（若啟用層級）、備註；提交後視設定發通知給管理者。
  - `TenantWelcomePage`
    - 可由租戶自訂的模組化版面：`HeroBanner`（標題、副標、背景圖）、`QuickStartSteps`（圖文步驟，特別說明首次登入後的操作順序）、`WeeklyReportDemo`（嵌入 `WelcomeShowcase` 或租戶自訂影片連結）、`Announcements`（最新公告）、`SupportContacts`（聯絡方式）。
    - 每個模組皆可由公司管理者啟用/停用並調整排序；未啟用時使用系統預設或隱藏。
    - 系統預設提供 `WelcomeShowcase` 元件，若租戶未自訂仍顯示預設引導。
    - 可配置 CTA：「登入」、「註冊」、自訂外部連結（例如內部 Wiki）。
  - `WelcomeShowcase`
    - 分步驟導覽或模擬互動表單，展示週報填寫流程與特色功能（拖曳排序、Redmine/Jira 自動帶入、工時合計、自動提醒、假日警示顏色）。
    - 支援在主站與租戶歡迎頁共用，並可套用租戶品牌樣式。
  - `SummaryPages`
    - `CompanySummaryPage`：顯示公司層級 KPI、各單位概況。
    - `DivisionSummaryPage` / `DepartmentSummaryPage` / `TeamSummaryPage`：共用 `SummaryHeader` 顯示名稱、週期、工時累計，支援層級切換與時間區間篩選。
    - `ExportButton`：依查詢條件匯出 CSV/XLSX，包含公司/單位/部門/小組對應資料。
    - `MemberAccordion`：展開顯示成員本週／下週事項，支援依小組分類。
  - `Shared`
    - `TwoFactorSetup`（若需讓使用者啟用/重設 2FA）。
    - `RoleGuard` 高階元件依 slug、角色、層級權限限制路由。
  - `HQPortalPages`
    - `CompanyListPage`：顯示所有租戶、狀態、成員數、最後活動時間。
    - `CompanyFormPage`：建立公司、指派 slug、設定預設品牌與公司管理者、設定初始成員上限（預設 50）。
    - `CompanyEditPage`：調整租戶狀態、人數上限、預設角色、品牌設定。
    - `WelcomeTemplateLibraryPage`：管理系統預設的歡迎頁模板，提供複製/分配到租戶、預覽與版本控制。
    - `AuditTrailPage`：查看租戶重要操作紀錄、狀態切換歷史。

- **狀態與資料流程**
  - 使用 `useQuery` 搭配 `queryKey` (e.g. `['weeklyReports', companySlug, filters]`)；slug 變動時重置 Cache。
  - 表單管理採 `react-hook-form`，結合 `zod` schema 驗證（對應後端 FormRequest）。
  - 將日期選擇器抽象成 `WeekPicker`（回傳週一日期與週數）以保持一致。

- **使用者體驗**
  - 失敗提示統一使用 `toast` (react-hot-toast) 或 `MUI Snackbar`。
  - 提供草稿自動儲存（localStorage 或後端 autosave）避免資料遺失。
  - 匯入上一週資料時顯示 diff 或關鍵字標記。
  - 介面語系鎖定為繁體中文，日期與時間一律採臺灣地區格式呈現。

## 4. 前後端串接與部署流程
- **開發環境**
  - Backend：`php 8.3`, `composer install`，`.env` 加入多租戶設定：`PRIMARY_DOMAIN`、`TENANT_SLUG_MODE`（subdomain/path）、`SANCTUM_STATEFUL_DOMAINS` 包含主網域與 wildcard 子網域。
  - Frontend：`node 20`, `npm install`，`.env` 配置 `VITE_PRIMARY_DOMAIN`、`VITE_TENANT_STRATEGY`、`VITE_APP_ENV`；開發伺服需支援 slug 子路徑或子網域代理。
  - 建議以 Laravel Sail 或 Docker Compose（PHP-FPM + Nginx + MySQL + Redis）維持一致環境，Nginx 設定 wildcard subdomain 或 rewrite slug path。

- **身份驗證流程（多租戶）**
  1. 使用者訪問 `/{company_slug}` 或 `{company_slug}.example.com`，Edge Middleware 驗證 slug → 載入公司設定（LOGO、主題色、公司狀態）。
  2. React 於租戶登入頁送出帳密至 `POST /api/v1/{company_slug}/auth/login`，搭配 Sanctum CSRF cookie。
  3. 若帳戶啟用 2FA，導向 `/two-factor-challenge`，提交 OTP 後獲得 Sanctum session cookie（cookie domain 覆蓋租戶子網域）。
  4. 後續 API 皆以 `/{company_slug}` 為前綴並附 `X-XSRF-TOKEN`，中介層檢查租戶 ID 與使用者角色授權。
  5. 主控台（HQ Portal）使用獨立域名，如 `https://hq.example.com`，登入後建立公司與核發 slug，與租戶 cookie 不互通。

- **API 版本管理**
  - 租戶 API 採 `Route::prefix('api/v1/{company:slug}')` 搭配 Route Model Binding。
  - HQ Portal 採 `Route::prefix('api/v1/hq')`，授權限定系統管理者。
  - 對外文件建議使用 Laravel OpenAPI（`scribe` 或 `laravel-swagger`）區分租戶與 HQ API。

- **部屬與 CI/CD**
  - Pipeline 範例：GitHub Actions
    - `phpstan`, `pint`, `pest` -> `npm run lint`, `npm run test` -> 建置前端 -> 部署到 staging/production。
  - 部署場景：Laravel 服務部署至 Kubernetes/EC2，Ingress/ALB 支援 wildcard 子網域；React 打包為靜態檔案交由 CDN/Nginx，rewrite 支援 slug path。
  - 設定計畫性排程（`php artisan schedule:run` via cron）發送提醒，使用 Queue tag 依公司分流。

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
- **測試策略**
  - 後端：Pest/PhpUnit 覆蓋 Service、Policy、API；使用 `laravel/testbench` 建立工作週模擬資料。
  - 前端：React Testing Library 覆蓋表單邏輯；Cypress 進行 E2E（登入 + 建立週報 + 匯出 CSV）。
  - 合約測試：利用 `pact` 或 `schemathesis` 確保前後端契約一致。

- **租戶設定與品牌**
  - `company_settings` 提供 UI 讓公司管理者調整層級啟用、提醒時段、匯出欄位模板、歡迎頁模組與登入 IP 白名單。
  - 品牌自訂：上傳 LOGO、設定主副色、登入頁背景與訊息公告；前端根據 slug 讀取設定動態載入 Theme。
  - 安全設定：可要求所有成員強制啟用 2FA、限制允許登入 IP 範圍（最多 5 組）、設定資料保留年限。

- **資料品質與維運**
  - 定期排程檢查重複週報、未完成報表並寄出提醒。
  - 透過資料庫 view 或 Looker Studio 進行報表分析（實際工時 vs 預估工時）。
  - 設計 `archived_weekly_reports` 表或冷資料儲存政策，避免主表膨脹。
  - 假期同步：排程透過政府開放資料平台 API 更新 `holidays` 表，記錄國定假日與補班日，供工時計算與提醒使用。

- **未來擴充方向**
  - 權限細分：支援跨部門協作或專案維度授權。
  - Redmine/Jira Webhook：自動同步 issue 狀態到週報。
  - 多語系支援、通知渠道擴充（LINE、Teams）。
  - 目標管理（OKR）或 KPI 模組整合，將週報連結到年度目標。
