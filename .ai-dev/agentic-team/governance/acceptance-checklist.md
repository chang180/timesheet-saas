# 檢視助手驗收清單

於 Orchestrator 宣布某史詩「可驗收」時，由**專案負責人**偕同 **Cursor 檢視助手** 逐項勾選。

## 文件與單一真相來源

- [ ] [.ai-dev/development/README.md](../../development/README.md) 鏈結 **Agentic Team** 與 **archive** 說明。
- [ ] [.ai-dev/agentic-team/README.md](../README.md) 與本治理目錄與實際改動一致。
- [ ] 作用中目錄無「`*_original`／`*_v2`」等與現行敘述並列之重複權威文件；大改版若保留快照，應在 [.ai-dev/archive/](../../archive/README.md) 有對應條目或僅依 Git 追溯（報告中註明）。

## Orchestrator 報告

- [ ] **待決策報告**含：可讀摘要、落差或風險、**需人類決策的明確問句**、建議下一步史詩 ID。
- [ ] **完成定義（DoD）**與實際交付一致；附 **驗收索引**（變更檔案路徑、建議執行的測試指令）。
- [ ] 四專責輸出已由 Orchestrator 合併；若有矛盾，報告中列為**選項**而非單方面定案。
- [ ] 若任務包含 **ToolExecutorAgent**：待決策報告中必須記錄「套用變更的檔案清單、測試指令與結果、commit/push 狀態（或無變更不 commit 的理由）」。

## Neuron／Inspector（可選）

- [ ] 若本次**無** Neuron 執行：報告中「執行觀測」為 **不適用**。
- [ ] 若有 Neuron 執行：報告含 Inspector 對照（時間區間或可辨識敘述）；`INSPECTOR_INGESTION_KEY` 僅經環境變數／`config`，未硬編於程式庫。

## 【最高警戒】開機路徑護欄

> 違反此項視同本次 Epic 交付失敗，必須 revert 並重新執行。

- [ ] 若本次有修改**任何**以下「開機路徑」檔案，ToolExecutorAgent 的執行記錄中必須包含 `http_healthcheck PASS`，才可允許 commit/push：
  - `bootstrap/app.php`、`bootstrap/providers.php`、`public/index.php`
  - `config/*.php`（任何設定檔）
  - `app/Http/Middleware/*.php`（任何 middleware）
  - `app/Providers/*.php`（任何 ServiceProvider）
  - `composer.json` / `composer.lock`
- [ ] 若上述健康檢查缺失：立即執行 `curl -s -o /dev/null -w "%{http_code}" https://timesheet-saas.test/` 確認為 `200`，並檢查回應不含 `Fatal error`。

**背景**：2026-03-26 EPIC-DEBUG-HOMEPAGE ——ToolExecutorAgent 修改 `bootstrap/app.php` 加入 `app()->environment('testing')` 判斷，在 PHP Unit test bootstrap 下可正常運作故測試通過，但 HTTP 啟動時 `env` service 尚未綁定導致 `ReflectionException: Class "env" does not exist`，造成首頁全面爆掉。單靠測試無法偵測開機期錯誤，**必須輔以 HTTP 健康檢查**。

## 程式與專案慣例

- [ ] 若有 PHP 改動：已執行 `vendor/bin/pint --dirty`（或 CI 等效）。
- [ ] 若有行為改動：已新增／更新 Pest（Feature 或 Browser 視情況）；至少執行 `php artisan test --compact` 且**相關**測試通過。
- [ ] 安全敏感變更已經 SecurityAgent 任務包覆蓋（middleware、Policy、租戶隔離）。

## 首次導入試跑紀錄（Epic 01）

2026-03-25：已完成開發文件對齊（過時區塊封存、`development/README`／`phase-4` 更新、`agentic-team` 目錄建立）。後續史詩請重填本清單。
