# Phase 2：前端開發與租戶體驗實作

> 目標：完成 React 前端與租戶歡迎頁客製化功能，確保使用者能從主站導引進入租戶入口、瀏覽歡迎展示並順利填寫週報。

## 📊 完成度：100%

**最後更新：** 2026-01-30

**專案現況對照（2026-01-30 檢視）**
- ✅ 個人週報功能已完整實作
- ✅ **組織層級管理與邀請連結功能已於 2026-01-02 完成**
- ✅ **週報編輯 UX 優化已於 2026-01-21 完成**（固定底部操作欄、智慧變更檢測、詳細錯誤提示、自動捲動至錯誤等）
- ✅ 成員管理頁面：`tenant/members/index.tsx` 已有完整篩選（角色、層級、關鍵字）
- ✅ 週報列表頁面：已實作 FiltersPanel（年度、狀態篩選）- 2026-01-30
- Redmine/Jira lookup：可選功能，暫不實作（僅欄位儲存）

---

## 歷史指示（已封存）

本處曾列「交給 AI」之未完成任務（Global Landing Browser 測試、FiltersPanel 等），內容已與現況脫節：例如 `tests/Browser/GlobalLandingTest.php` 與週報列表 FiltersPanel 業已對應實作。舊文已移至 [.ai-dev/archive/development/phase-2-superseded-ai-instructions-2026-03-25.md](../archive/development/phase-2-superseded-ai-instructions-2026-03-25.md)。新需求請另開史詩，由 [.ai-dev/agentic-team/](../agentic-team/) 流程派工。

---

## 1. 前端基礎設置

- **環境要求**
  - TypeScript 5.x、Vite 5.x
  - React 19.x、`@inertiajs/react` v2
  - Laravel Wayfinder（TypeScript route helpers）
  - UI 套件：Tailwind CSS v4 + Headless UI（沿用既有元件庫）      
- **初始化任務**
  1. 確認 `resources/js/` 目錄結構（實際路徑小寫）：
     - `resources/js/app.tsx`：Inertia + React 入口。
     - `resources/js/pages/`：Inertia Page（`landing/global-landing.tsx`、`landing/tenant-welcome.tsx`、`tenant/...`、`weekly/...` 等）。
     - `resources/js/components/`：共用元件、表單與 UI 套件。
     - `resources/js/tenant/welcome-modules/`：租戶歡迎頁模組（Hero、Quick Steps、Announcements、SupportContacts、WeeklyReportDemo）。
     - `@/routes/`、`@/actions/`：Wayfinder 產生的路由 helper（build 時生成，import 自 `@/routes/tenant` 等）。
  2. 利用 Wayfinder 匯入 `@/actions/...`、`@/routes/...`，統一處理主站與租戶路由，避免手寫 URL。
  3. 以 Inertia `<Form>` 元件或 `router.visit()` 進行導覽與資料提交；保留 `react-hot-toast` 作為互動提示。

**驗收檢查**
- ✅ `npm run dev` 可透過 Inertia 頁面同時呈現主站 (`/`) 與租戶 (`/tenant/...`) 流程。
- ✅ 透過 Wayfinder helper 切換 `companySlug` 時，Inertia props 與快取資料可正確刷新。
- ✅ 已完成：`resources/js/app.tsx`、Wayfinder routes、welcome-modules 目錄結構

## 2. 主站體驗（Global Landing）

1. ✅ 建立 `GlobalLandingPage` Inertia 頁面：
   - ✅ Hero 區塊：介紹產品價值、CTA 按鈕（登入 / 申請試用），使用 `@inertiajs/react` 的 `<Link>` 或 Wayfinder `landing.show()`。
   - ✅ `WelcomeShowcase` Demo：採用模擬資料顯示週報填寫步驟（拖曳排序、工時合計；Redmine 帶入、假日警示為可選／Phase 3）。
   - ✅ 快速上手步驟：列出租戶啟用所需設定（品牌、層級、提醒、防火牆）。
2. ✅ 若提供 Demo 租戶（可選），透過 `.env` 與後端 props 控制顯示，並使用 Inertia deferred props 減少初次載入負擔。
3. ✅ 撰寫 Pest Browser 測試：`tests/Browser/GlobalLandingTest.php` - 2026-01-30

## 3. 租戶歡迎頁（Tenant Landing）

1. ✅ 建立 `TenantWelcomePage` Inertia 頁面：
   - ✅ 依 `company_settings.welcome_page` JSON 動態組裝 `resources/js/tenant/welcome-modules/` 模組（Hero、QuickStartSteps、WeeklyReportDemo、Announcements、SupportContacts）。
   - ✅ 模組排序與顯示由設定控制；若未啟用則使用預設內容或隱藏。
   - ✅ 嵌入 `WelcomeShowcase`（可套用租戶品牌色），或播放租戶自訂影片 `videoUrl`。品牌色可透過 Inertia props 帶入 Tailwind CSS CSS variables。
2. ✅ 提供 `TenantWelcomeConfigurator`（公司管理者用）：
   - ✅ 使用 Inertia `<Form>` 或 `useForm` 搭配 Wayfinder `Tenant\WelcomePageController@update` action。
   - ✅ 表單欄位對應 JSON schema，預覽模式可透過前端 state 即時更新。
   - ✅ 限制 Quick Steps 最多 5 項、CTA 最多 3 個，並於前端校驗。
3. ✅ 建立 `IPWhitelistForm` 元件：
   - ✅ 採用 Inertia `<Form>` 送出至 `Tenant\IpWhitelistController`。
   - ✅ 驗證 IPv4/IPv6/CIDR 格式，採用 `@/components/ui/input` + 自訂錯誤提示。

**驗收檢查**
- ✅ 租戶歡迎頁可根據設定即時刷新 UI，Inertia response 變更後模組順序無需整頁刷新。
- ✅ 當 JSON 設定缺漏時，顯示預設文案並透過後端 `logWarning` 記錄。
- ✅ 公司管理者更新後，Inertia toast / 或 `recentlySuccessful` 狀態顯示成功/失敗提示。

## 4. 核心週報介面

### 4.1 週報列表 (`weekly/list.tsx`)
- ✅ 已實現：基本列表顯示、工時統計、狀態標記、補填週報 Dialog、最近週報表格
- ✅ FiltersPanel：年度篩選、狀態篩選（2026-01-30 實作）
- ⚠️ 匯出功能：CSV/XLSX 按鈕（Phase 3 實作，本階段可略）

### 4.2 週報表單 (`WeeklyReportFormPage`)
- ✅ 使用 Inertia `<Form>` 或 `useForm` 搭配 `zod` schema 進行前端驗證。
- ✅ `CurrentWeekSection` / `NextWeekSection`：
  - ✅ 支援拖曳排序（@dnd-kit），同步更新 `useForm` 狀態。
  - Redmine/Jira lookup：可選功能，暫不實作；僅有 UI 欄位儲存，無查詢 API。
- ✅ `TotalsSummary`：工時合計顯示已實作，顯示本週完成總工時和下週預計總工時。
- ✅ `CopyPreviousWeek` 功能：已實現 `prefillFromPreviousWeek`，自動帶入上一週的下週預計項目。
- ⚠️ 假日警示功能（Phase 3 功能，待 Phase 3 實作）。

### 4.3 自助註冊與登入
- ✅ `SelfRegistrationPage`：Inertia `<Form>` 已實現，顯示公司名稱、姓名、Email 等欄位。
- ✅ 登入頁：沿用租戶品牌色、LOGO、公告與支援資訊，透過 Inertia props 動態套用。

**驗收檢查**
- ✅ Pest Browser 測試：`tests/Browser/WeeklyReportFlowTest.php` 涵蓋登入、建立、查看、編輯週報流程。
- 註：專案未設定 Jest/Vitest，前端元件單元測試可選；表單驗證由後端 FormRequest + 前端 zod 處理。

## 5. Frontend API 封裝

- ✅ 善用 Wayfinder `@/actions/...` 及 `@/routes/...`：
  - ✅ GET/POST/PUT/DELETE 行為以 `action.post()`、`action.put()` 等方法產生 URL 與 method。
  - ✅ Frontend 呼叫使用 Inertia `router.visit()`、`router.reload()` 或 `<Form {...action.form()}>`。
- ⚠️ 針對頻繁變動或背景更新資料，可結合 `@tanstack/react-query`（目前未整合，僅使用 Inertia props）。
- ⚠️ 統一在 `resources/js/hooks/` 建立封裝：
  - ⚠️ `useTenantSettings`, `useWelcomePageConfig`, `useWeeklyReports`, `useSummaryReports`, `useHolidayCalendar`（待建立）。
- ✅ Mutation 透過 Wayfinder action + Inertia form，成功後以 `router.reload({ only: [...] })` 更新最新資料；錯誤時顯示 toast 並保留表單錯誤狀態。

**驗收檢查**
- ✅ 所有 API 呼叫皆由 Wayfinder 生成 URL，並透過 Inertia 管理狀態。
- ✅ 切換 `companySlug` 時，Inertia props 可正確重置，避免跨租戶資料串流。
- ⚠️ React Query 整合（可選，目前未使用）。

## 6. 任務分配建議

- **前端工程師 / AI 助理**
  - 組件與頁面實作、互動體驗。
  - State 管理、React Query。
- **UI/UX**
  - 設計歡迎頁模板與品牌化指引。
  - 制定假日警示色彩規範。
- **QA**
  - 針對多租戶 slug 測試。
  - 各層級匯出檢驗與假日提醒。

## 7. 風險與注意事項

- **品牌化**：透過 Tailwind CSS 與 CSS Variables 套用租戶品牌色，避免樣式污染。
- **歡迎頁 JSON**：加入 schema 驗證與 fallback，以免租戶輸入錯誤資料造成頁面崩壞。
- **IP 白名單**：前端需對輸入格式做初步檢查，減少 API 失敗。
- **多租戶導覽**：切換 slug 時，以 Inertia partial reload 或 React Query `resetQueries` 清除跨租戶狀態。
- **TOC 與導覽**：考慮提供租戶設定完成導覽 checklist，搭配 Inertia flash message 提醒。
- **組織層級設定**：移除層級前需確保該層級下無資料，避免資料不一致。已實作驗證邏輯於 `UpdateOrganizationLevelsRequest`。
- **邀請連結安全性**：使用足夠長度的隨機 token（64 字元），確保連結安全性。邀請連結永久有效，可隨時啟用/停用。

> Phase 2 已於 2026-01-30 全數完成。核心功能包含組織層級管理、邀請連結、主站 Browser 測試、週報列表 FiltersPanel。可進入 Phase 3 處理通知、報表與安全性測試。

---

## 📋 完成狀態總結

### ✅ 已完成（約 95-100%）

1. **前端基礎設置** - 100%
   - ✅ Inertia + React 19 + TypeScript 5.x
   - ✅ Wayfinder 路由整合
   - ✅ Tailwind CSS v4
   - ✅ 目錄結構完整

2. **主站體驗** - 100%
   - ✅ GlobalLandingPage 完整實現
   - ✅ WelcomeShowcase Demo
   - ✅ 快速上手步驟
   - ✅ Demo 租戶支援

3. **租戶歡迎頁** - 100%
   - ✅ TenantWelcomePage 動態模組
   - ✅ TenantWelcomeConfigurator 設定器
   - ✅ IPWhitelistForm
   - ✅ 所有歡迎頁模組（5個）

4. **核心週報介面** - 95%
   - ✅ WeeklyReportListPage（`weekly/list.tsx`）
   - ✅ WeeklyReportFormPage（`weekly/form.tsx`）
   - ✅ 拖曳排序（@dnd-kit）
   - ✅ 複製上週功能
   - ✅ 工時合計顯示（TotalsSummary）
   - ⚠️ 週報列表 FiltersPanel（選做，見任務 2）
   - ⚠️ Redmine/Jira lookup API（保留彈性，先不做）
   - ⚠️ 假日警示（Phase 3）

5. **登入/註冊** - 100%
   - ✅ 註冊頁面
   - ✅ 登入頁品牌化
   - ✅ **邀請連結註冊頁面**（2026-01-02 新增）

6. **組織層級管理** - 100% ✅
   - ✅ 組織層級彈性設定（Division/Department/Team 可選）
   - ✅ 各層級專屬邀請連結生成與管理
   - ✅ 層級管理者權限控制
   - ✅ 邀請連結註冊流程
   - ✅ 完整的後端 API 和前端 UI

7. **後端與測試** - 95%
   - ✅ WeeklyReportController
   - ✅ Feature 測試：Landing、Auth、Settings、Tenant、WeeklyReport、Hq、WeeklyReportFilter 等
   - ✅ Pest Browser：`WeeklyReportFlowTest.php`、`OrganizationInvitationFlowTest.php`、`GlobalLandingTest.php`

8. **成員管理頁面** - 100% ✅
   - ✅ `tenant/members/index.tsx` 含完整篩選（角色、division/department/team、關鍵字）
   - ✅ 成員邀請、角色編輯、組織樹狀顯示
   - 註：週報列表的 FiltersPanel 為不同功能，見任務 2。

9. **組織層級管理與邀請連結** - 100% ✅
   - ✅ 資料庫遷移（organization_levels, invitation_token, invitation_enabled）
   - ✅ Model 更新（CompanySetting, Division, Department, Team）
   - ✅ Controllers（OrganizationLevelsController, *InvitationController）
   - ✅ Form Requests（UpdateOrganizationLevelsRequest, GenerateInvitationRequest, ToggleInvitationRequest）
   - ✅ 路由（API 和 Web 路由）
   - ✅ 前端元件（OrganizationLevelsCard, OrganizationInvitationSection）
   - ✅ 公開註冊頁面（register-by-invitation.tsx）
   - ✅ 權限控制（層級管理者權限驗證）
   - ✅ 組織層級彈性設定（公司管理者可選擇使用的層級）
   - ✅ 各層級專屬邀請連結生成與管理
   - ✅ 邀請連結註冊流程（自動加入對應層級）

### ⚠️ 待完成項目（可延後或移除）

#### 選做 / 低優先級
1. ~~**主站 Global Landing Pest Browser 測試**~~ - ✅ 已於 2026-01-30 完成
2. ~~**週報列表 FiltersPanel**~~ - ✅ 已於 2026-01-30 完成
3. **成員管理前端頁面優化** - 邀請表單可進一步整合層級邀請連結；角色編輯可增加批量操作。基本功能已完成。
4. **Redmine/Jira lookup API** - 僅有 UI 欄位，保留彈性，先不做。
5. **週報匯出功能** - Phase 3 處理。
6. **React Query 整合** - 可選，目前僅使用 Inertia props。
7. **自訂 hooks** - useTenantSettings, useWeeklyReports 等，可選。
8. **假日警示功能** - Phase 3 功能。

### 📝 已完成項目

#### 2025-12-13
1. ✅ **工時合計顯示（TotalsSummary）** - 已在週報表單中實作，顯示本週完成總工時和下週預計總工時
2. ✅ **Pest Browser 測試** - 已建立 `tests/Browser/WeeklyReportFlowTest.php`，包含登入、建立、查看、編輯週報的完整流程測試

#### 2026-01-21
3. ✅ **週報編輯 UX 優化** - 固定底部操作欄、新增項目按鈕與空白狀態優化；完整表單反饋（成功訊息、詳細錯誤、智慧變更檢測、沒有變更提示）；自動捲動至錯誤、Flash 訊息中間件修正、已發佈週報狀態提示。

#### 2026-01-02
4. ✅ **組織層級彈性設定功能** - 完整實作，包含前端 UI 和後端 API
5. ✅ **各層級專屬邀請連結功能** - 完整實作，包含生成、啟用/停用、管理功能
6. ✅ **邀請連結註冊流程** - 完整實作，包含公開註冊頁面和後端處理
7. ✅ **層級管理者權限控制** - 完整實作，確保各層級管理者只能管理自己的邀請連結
8. ✅ **資料庫遷移** - 完成所有必要的資料庫結構變更
9. ✅ **路由與 API** - 完成所有必要的路由和 API 端點
10. ✅ **前端元件** - 完成所有必要的 React 元件和頁面

#### 2026-01-30
11. ✅ **主站 Global Landing Pest Browser 測試** - `tests/Browser/GlobalLandingTest.php`（8 個測試案例）
12. ✅ **週報列表 FiltersPanel** - 後端支援 `filter_year`、`filter_status` 參數；前端新增篩選 UI
13. ✅ **週報篩選 Feature 測試** - `tests/Feature/WeeklyReportFilterTest.php`（7 個測試案例）

### 📝 待處理項目

#### ✅ 已完成
- ✅ 主站 Global Landing Pest Browser 測試（任務 1）- 2026-01-30
- ✅ 週報列表 FiltersPanel（任務 2）- 2026-01-30

#### 測試相關（Phase 3 處理）
- 組織層級管理功能測試：已有 `OrganizationInvitationFlowTest.php`、`OrganizationLevelsTest.php` 等，可視需求補充。
- 邀請連結流程測試：`OrganizationInvitationFlowTest.php`、`RegisterByInvitationTest.php` 已存在。

#### 可延後或移除
- Redmine/Jira lookup API、週報匯出、React Query、自訂 hooks、假日警示、成員管理優化：見上方「選做 / 低優先級」。
