# Phase 2：前端開發與租戶體驗實作

> 目標：完成 React 前端與租戶歡迎頁客製化功能，確保使用者能從主站導引進入租戶入口、瀏覽歡迎展示並順利填寫週報。

## 1. 前端基礎設置

- **環境要求**
  - TypeScript 5.x、Vite 5.x
  - React 18.x、React Router 6.x、React Query 5.x（或新版）
  - UI 套件建議：Tailwind CSS + Headless UI / MUI（依團隊選擇）
- **初始化任務**
  1. 設定 `src/` 目錄結構：
     - `src/api/`：封裝 axios/fetch client，套用 slug 與 CSRF。
     - `src/hooks/`：Tenant context、權限與資料讀取 hooks。
     - `src/pages/`：按路由拆分頁面。
     - `src/components/`：共用元件、表單、Table。
     - `src/tenant/`：歡迎頁模組、品牌樣式、設定轉換。
  2. 建立 `src/routes/root.tsx`，支援主站與租戶子路由。
  3. 整合 `react-hot-toast` 或 Snackbar，用於 API 提示。

**驗收檢查**
- `npm run dev` 可同時服務主站 (`/`) 與租戶子路徑 (`/:companySlug`)。
- Hashed query keys包含 `companySlug`，slug 變動時 Query Cache reset。

## 2. 主站體驗（Global Landing）

1. 建立 `GlobalLandingPage`：
   - Hero 區塊：介紹產品價值、CTA 按鈕（登入 / 申請試用）。
   - `WelcomeShowcase` Demo：採用模擬資料顯示週報填寫步驟（拖曳排序、Redmine 帶入、工時合計、假日警示）。
   - 快速上手步驟：列出租戶啟用所需設定（品牌、層級、提醒、防火牆）。
2. 若提供 Demo 租戶（可選），需透過 `.env` 控制是否顯示。
3. 撰寫 Cypress/Playwright 測試：確認 CTA 正常導航、示範動畫顯示。

## 3. 租戶歡迎頁（Tenant Landing）

1. 建立 `TenantWelcomePage`：
   - 依 `company_settings.welcome_page` JSON 動態組裝模組（Hero、QuickStartSteps、WeeklyReportDemo、Announcements、SupportContacts）。
   - 模組排序與顯示由設定控制；若未啟用則使用預設內容或隱藏。
   - 嵌入 `WelcomeShowcase`（可套用租戶品牌色），或播放租戶自訂影片 `videoUrl`。
2. 提供 `TenantWelcomeConfigurator`（公司管理者用）：
   - 表單欄位對應 JSON schema。
   - 預覽模式：即時展示 Hero/Steps/CTA 效果。
   - 限制 Quick Steps 最多 5 項、CTA 最多 3 個。
3. 建立 `IPWhitelistForm` 元件，讓公司管理者輸入最多 5 組 IPv4/IPv6/CIDR。

**驗收檢查**
- 租戶歡迎頁可根據設定即時刷新 UI。
- 當 JSON 設定缺漏時，顯示預設文案且記錄 warning log。
- 公司管理者更新後收到成功/失敗提示。

## 4. 核心週報介面

### 4.1 週報列表 (`WeeklyReportListPage`)
- FiltersPanel：週期、單位/部門/小組層級、狀態、關鍵字。
- Table：
  - 使用 TanStack Table 或 MUI DataGrid。
  - 顯示提交狀態、工時統計、假日異常標記。
  - 提供 CSV/XLSX 匯出快捷按鈕（呼叫 Phase 3 API）。

### 4.2 週報表單 (`WeeklyReportFormPage`)
- `react-hook-form` + `zod` schema。
- `CurrentWeekSection` / `NextWeekSection`：
  - 支援拖曳排序（React Beautiful DnD 或 dnd-kit）。
  - Redmine/Jira lookup：輸入 issue 後調用 API 填入標題、預估工時。
- `TotalsSummary`：顯示工時合計、週工時上限、假日警示（來自 Phase 3 假期資料）。
- `CopyPreviousWeek` 按鈕：帶入上一週資料（提示使用者確認）。

### 4.3 自助註冊與登入
- `SelfRegistrationPage`：顯示剩餘名額、可選部門/小組、送出後顯示 Email 驗證提示。
- 登入頁：顯示租戶品牌色、LOGO、公告與支援資訊。

**驗收檢查**
- Jest/RTL 測試表單驗證、假日警示、拖曳排序更新順序。
- E2E 測試：註冊 → 登入 → 建立週報 → 查看列表 → 匯出。

## 5. Frontend API 封裝

- 建立 `api/client.ts`：
  - 自動處理 slug prefix、CSRF。
  - 封裝錯誤處理（顯示 toast、導向 403/404 頁面）。
- 建立 Query Hooks：
  - `useTenantSettings`, `useWelcomePageConfig`, `useWeeklyReports`, `useSummaryReports`, `useHolidayCalendar`。
- Mutation Hooks：
  - `useUpdateWelcomePage`, `useUpdateIPWhitelist`, `useSubmitWeeklyReport`, `useReopenWeeklyReport`。

**驗收檢查**
- Query Key 以 `[companySlug, 'weeklyReports', filters]` 形式組成。
- Mutation 成功後能自動 `invalidateQueries` 更新最新資料。

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

- **品牌化**：需確認 CSS 名稱空間，避免不同租戶樣式互相污染。
- **歡迎頁 JSON**：需加入 schema 驗證與 fallback，以免租戶輸入錯誤資料造成頁面崩壞。
- **IP 白名單**：前端需對輸入格式做初步檢查，減少 API 失敗。
- **多租戶導覽**：切換 slug 時需清除所有狀態（React Query Cache、Zustand/Redux 狀態）。
- **TOC 與導覽**：考慮提供租戶設定完成導覽 checklist，避免遺漏提醒。

> 完成 Phase 2 後，即可進入 Phase 3 處理通知、報表與安全性測試。

