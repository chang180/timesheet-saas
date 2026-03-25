# Neuron PHP 可執行管線（後續迭代）

**狀態**：刻意延後實作。

本專案已於 `composer.json` 依賴 `neuron-core/neuron-ai`，但 **應用程式內尚未** 建立可重複執行的 Neuron Agent／Workflow 管線（例如 Artisan 指令觸發多步驟、佇列 worker）。

## 後續可納入範圍（非承諾）

- 最小 Artisan 指令：讀取 diff 或 Issue 編號，呼叫單一 Agent，輸出 Markdown 報告。
- 與 [inspector.md](inspector.md) 搭配之 `INSPECTOR_INGESTION_KEY` 與環境分層（staging only）。
- CI 邊界：預設**不**在無密鑰的 CI 中呼叫付費 LLM；改以本機或手動工作流觸發。

導入時請更新本檔與 [README.md](../README.md) 之「Neuron」段落，並由 Orchestrator 另開專屬史詩。
