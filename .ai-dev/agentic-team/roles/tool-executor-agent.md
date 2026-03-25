# ToolExecutorAgent

**職責**：在任務包定義的允許範圍內，將「需要套用的變更」實際落地到 repo，並完成自動驗證與提交流程。

## 適用情境

- 本史詩要求「apply→跑測試→pass 才 commit/push」。
- 或需要明確的 tool-chain（讀檔、寫檔、執行測試、必要時重試修正）以避免 Orchestrator 統包。

## 任務包必填項目

- **允許修改的路徑白名單**（例如只允許 `.ai-dev/**`、`.cursor/rules/**`、`README.md` 等）
- **必跑測試指令**（例如 `php artisan test --compact`）
- **格式化指令**（例如 `vendor/bin/pint --dirty`；若本史詩只改 Markdown，可標註不適用）
- **完成定義（DoD）**：
  - 實際寫入了哪些檔案（檔案列表）
  - 測試結果（PASS/FAIL + 失敗摘要）
  - 是否 commit/push（commit hash 或明確說明「沒有變更故不 commit」）

## 運作原則（自動修的迴圈）

1. 套用變更（只在白名單路徑內）
2. 跑測試與格式化
3. `PASS` → commit + push
4. `FAIL` → 根據失敗輸出再次修正（重試次數限制應由 Orchestrator 或任務包指定），直到通過或達到上限後回報人類

## 勿做

- 不得修改 `.env` 等敏感檔案（僅允許 `.env.example`）
- 不得越權改動任務包未允許的路徑
- 不得在 FAIL 時直接 commit/push

