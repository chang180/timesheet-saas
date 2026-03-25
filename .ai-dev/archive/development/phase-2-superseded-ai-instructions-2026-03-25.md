# 封存：Phase 2「交給 AI」指示區塊（已參考終）

**封存日期**：2026-03-25  
**理由**：下列任務描述與現況不符或已完成（例如 Global Landing Browser 測試已存在、FiltersPanel 已完成）。現況請以現行 `phase-2.md` 內「專案現況對照」與後續章節勾選為準。

---

## 交給 AI 的明確指示（Phase 2 未完成工作）

若你（Claude、Cursor、Codex 或其他 AI）要完成本階段未完成的工作，請**僅**實作以下項目，並**不要**修改文件中已標記 ✅ 的功能或既有流程。

### 任務 1：主站 Global Landing 的 Pest Browser 測試（選做，低優先級）

**目標**：為 `landing/global-landing.tsx` 撰寫 Pest Browser 測試，確認 CTA 按鈕導向正確、Inertia 導覽無全頁刷新。

**做法**：

1. 參考 `tests/Browser/WeeklyReportFlowTest.php` 的寫法（使用 `visit()`、`click()`、`waitForRoute()` 等）。
2. 新增 `tests/Browser/GlobalLandingTest.php` 或於現有 Browser 測試中擴充。
3. 測試案例建議：
   - 訪問 `/`（或 `route('landing.global')`），確認頁面渲染。
   - 點擊「登入」或「申請試用」CTA，確認導向登入/註冊頁面。
   - 確認 Inertia 導覽後無整頁重新載入（可檢查 DOM 是否保留 SPA 結構）。
4. 執行：`php artisan test --compact tests/Browser/`。

**程式碼錨點**：

- 主站路由：`route('landing.global')`，定義於 `routes/web.php`。
- 頁面元件：`resources/js/pages/landing/global-landing.tsx`。
- 既有 Feature 測試：`tests/Feature/LandingControllerTest.php`（可參考 Inertia 斷言）。

---

### 任務 2：週報列表 FiltersPanel（選做，依需求決定）

**目標**：在 `weekly/list.tsx` 新增篩選面板，讓使用者可依週期、狀態篩選週報。

**前置說明**：目前 `WeeklyReportController::index` 僅回傳**當前登入使用者**的週報，且未支援 query 參數。若需篩選，需同時修改後端與前端。

**做法（簡要）**：

1. **後端**：於 `WeeklyReportController::index` 接受 `year`、`week`、`status` 等 query 參數，調整 `WeeklyReport::query()` 的 `where` 條件。
2. **前端**：於 `resources/js/pages/weekly/list.tsx` 新增篩選表單（Select/Input），使用 `router.get()` 或 `Link` 帶上 query 參數重新載入頁面。
3. 參考 `resources/js/pages/tenant/members/index.tsx` 的 `filters` 與 `useEffect` 實作模式。
4. 撰寫 Feature Test 驗證後端篩選邏輯。

**注意**：若專案僅需「我的週報」列表且無篩選需求，此任務可跳過。

---

### 任務 3：週報匯出功能（Phase 3 處理，本階段不實作）

匯出 CSV/XLSX 規劃於 Phase 3，見 `.ai-dev/development/phase-3.md`。本階段不實作。

---

### 驗收與交付

- 完成後請在本文件「完成狀態總結」或「待處理項目」區塊註記對應任務已完成。
- 若有新增測試，請執行 `php artisan test --compact` 確保全部通過。
- 若有修改 PHP，請執行 `vendor/bin/pint --dirty` 確保程式碼風格一致。
