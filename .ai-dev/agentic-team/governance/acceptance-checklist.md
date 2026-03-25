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

## Neuron／Inspector（可選）

- [ ] 若本次**無** Neuron 執行：報告中「執行觀測」為 **不適用**。
- [ ] 若有 Neuron 執行：報告含 Inspector 對照（時間區間或可辨識敘述）；`INSPECTOR_INGESTION_KEY` 僅經環境變數／`config`，未硬編於程式庫。

## 程式與專案慣例

- [ ] 若有 PHP 改動：已執行 `vendor/bin/pint --dirty`（或 CI 等效）。
- [ ] 若有行為改動：已新增／更新 Pest（Feature 或 Browser 視情況）；至少執行 `php artisan test --compact` 且**相關**測試通過。
- [ ] 安全敏感變更已經 SecurityAgent 任務包覆蓋（middleware、Policy、租戶隔離）。

## 首次導入試跑紀錄（Epic 01）

2026-03-25：已完成開發文件對齊（過時區塊封存、`development/README`／`phase-4` 更新、`agentic-team` 目錄建立）。後續史詩請重填本清單。
