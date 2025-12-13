# Phase 2：前端開發與租戶體驗實作

> 目標：完成 React 前端與租戶歡迎頁客製化功能，確保使用者能從主站導引進入租戶入口、瀏覽歡迎展示並順利填寫週報。

## 📊 完成度：約 85-90%

**最後更新：** 2025-12-13

## 1. 前端基礎設置

- **環境要求**
  - TypeScript 5.x、Vite 5.x
  - React 19.x、`@inertiajs/react` v2
  - Laravel Wayfinder（TypeScript route helpers）
  - UI 套件：Tailwind CSS v4 + Headless UI（沿用既有元件庫）
- **初始化任務**
  1. 確認 `resources/js/` 目錄結構：
     - `resources/js/app.tsx`：Inertia + React 入口。
     - `resources/js/pages/`：Inertia Page（`landing/global-landing.tsx`、`landing/tenant-welcome.tsx`、`tenant/...` 等）。
     - `resources/js/components/`：共用元件、表單與 UI 套件。
     - `resources/js/tenant/welcome-modules/`：租戶歡迎頁模組（Hero、Quick Steps、Announcements 等）。
     - `resources/js/routes/`：Wayfinder 產生的 action 與路由 helper。
  2. 利用 Wayfinder 匯入 `@/actions/...`、`@/routes/...`，統一處理主站與租戶路由，避免手寫 URL。
  3. 以 Inertia `<Form>` 元件或 `router.visit()` 進行導覽與資料提交；保留 `react-hot-toast` 作為互動提示。

**驗收檢查**
- ✅ `npm run dev` 可透過 Inertia 頁面同時呈現主站 (`/`) 與租戶 (`/tenant/...`) 流程。
- ✅ 透過 Wayfinder helper 切換 `companySlug` 時，Inertia props 與快取資料可正確刷新。
- ✅ 已完成：`resources/js/app.tsx`、Wayfinder routes、welcome-modules 目錄結構

## 2. 主站體驗（Global Landing）

1. ✅ 建立 `GlobalLandingPage` Inertia 頁面：
   - ✅ Hero 區塊：介紹產品價值、CTA 按鈕（登入 / 申請試用），使用 `@inertiajs/react` 的 `<Link>` 或 Wayfinder `landing.show()`。
   - ✅ `WelcomeShowcase` Demo：採用模擬資料顯示週報填寫步驟（拖曳排序、Redmine 帶入、工時合計、假日警示）。
   - ✅ 快速上手步驟：列出租戶啟用所需設定（品牌、層級、提醒、防火牆）。
2. ✅ 若提供 Demo 租戶（可選），透過 `.env` 與後端 props 控制顯示，並使用 Inertia deferred props 減少初次載入負擔。
3. ⚠️ 撰寫 Playwright / Pest Browser 測試：確認 CTA 導向、示範動畫、Inertia 導覽無全頁刷新。（待完成）

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

### 4.1 週報列表 (`WeeklyReportListPage`)
- ✅ 已實現：基本列表顯示、工時統計、狀態標記
- ⚠️ FiltersPanel：週期、單位/部門/小組層級、狀態、關鍵字（部分實現，待完善）
- ⚠️ 匯出功能：CSV/XLSX 按鈕（待實現）

### 4.2 週報表單 (`WeeklyReportFormPage`)
- ✅ 使用 Inertia `<Form>` 或 `useForm` 搭配 `zod` schema 進行前端驗證。
- ✅ `CurrentWeekSection` / `NextWeekSection`：
  - ✅ 支援拖曳排序（@dnd-kit），同步更新 `useForm` 狀態。
  - ⚠️ Redmine/Jira lookup：僅有 UI 欄位，無實際 API 實作（待實現）。
- ⚠️ `TotalsSummary`：工時合計顯示（待確認是否在表單中顯示）。
- ✅ `CopyPreviousWeek` 功能：已實現 `prefillFromPreviousWeek`，自動帶入上一週的下週預計項目。
- ⚠️ 假日警示功能（Phase 3 功能，待 Phase 3 實作）。

### 4.3 自助註冊與登入
- ✅ `SelfRegistrationPage`：Inertia `<Form>` 已實現，顯示公司名稱、姓名、Email 等欄位。
- ✅ 登入頁：沿用租戶品牌色、LOGO、公告與支援資訊，透過 Inertia props 動態套用。

**驗收檢查**
- ✅ Jest/RTL 測試：表單驗證、拖曳排序更新順序（部分完成）。
- ⚠️ Pest Browser 測試：註冊 → 登入 → 建立週報 → 查看列表 → 匯出（待完成）。

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

> 完成 Phase 2 後，即可進入 Phase 3 處理通知、報表與安全性測試。

---

## 📋 完成狀態總結

### ✅ 已完成（約 85-90%）

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

4. **核心週報介面** - 90%
   - ✅ WeeklyReportListPage
   - ✅ WeeklyReportFormPage
   - ✅ 拖曳排序（@dnd-kit）
   - ✅ 複製上週功能
   - ⚠️ Redmine/Jira lookup API（待實現）
   - ⚠️ 工時合計顯示（待確認）
   - ⚠️ 假日警示（Phase 3）

5. **登入/註冊** - 100%
   - ✅ 註冊頁面
   - ✅ 登入頁品牌化

6. **後端與測試** - 100%
   - ✅ WeeklyReportController
   - ✅ 6 個 Feature 測試通過
   - ✅ Settings 測試通過

### ⚠️ 待完成項目

1. **Redmine/Jira lookup API** - 僅有 UI 欄位，需實作後端 API（保留彈性，先不做）
2. ~~**工時合計顯示** - 需確認是否在表單中顯示 TotalsSummary~~ ✅ **已完成**
3. **週報匯出功能** - CSV/XLSX 匯出（先不做）
4. **React Query 整合** - 可選，目前僅使用 Inertia props（先不做）
5. **自訂 hooks** - useTenantSettings, useWeeklyReports 等（先不做）
6. ~~**Pest Browser 測試** - 端到端測試（待撰寫）~~ ✅ **已完成**
7. **假日警示功能** - Phase 3 功能（下一階段再做）

### 📝 已完成項目（2025-12-13）

1. ✅ **工時合計顯示（TotalsSummary）** - 已在週報表單中實作，顯示本週完成總工時和下週預計總工時
2. ✅ **Pest Browser 測試** - 已建立 `tests/Browser/WeeklyReportFlowTest.php`，包含登入、建立、查看、編輯週報的完整流程測試

### 📝 待處理項目（依用戶指示）

1. **Redmine/Jira lookup API** - 保留彈性，先不做
2. **週報匯出功能** - 先不做
3. **React Query 整合** - 先不做
4. **自訂 hooks** - 先不做
5. **假日警示功能** - Phase 3 階段處理

