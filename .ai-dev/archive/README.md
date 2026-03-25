# 開發文件封存庫（archive）

本目錄存放 **已被取代、僅供對照** 的文件快照。專案中 **作用中** 的開發規格與階段說明請一律以下列路徑為準：

- [.ai-dev/development/README.md](../development/README.md)（階段總覽與索引）
- [.ai-dev/laravel_weekly_report_spec.md](../laravel_weekly_report_spec.md)（系統規格）
- [.ai-dev/agentic-team/README.md](../agentic-team/README.md)（多角色開發流程）

## 維護原則

1. **單一最新版**：`development/`、`agentic-team/` 等主線目錄不與舊版副本並列；更新時直接改現行檔案。
2. **何時封存**：大段敘述汰換、或易誤導之舊指示（例如已完成卻仍寫「待實作」）可將**取代前全文**複製到 `archive/development/`（建議檔名含日期或史詩 ID）。
3. **真相來源**：**以 Git 歷史為準**；`archive/` 僅提升可讀性，必要時用 `git log`、`git show` 還原任意版本。

## 封存索引

| 封存檔 | 說明 |
|--------|------|
| [development/phase-0-1-superseded-hq-instructions-2026-03-25.md](development/phase-0-1-superseded-hq-instructions-2026-03-25.md) | Phase 0-1 中已過時的「HQ Portal 未完成」AI 指示（HQ 已於 2026-01-30 完成） |
| [development/phase-2-superseded-ai-instructions-2026-03-25.md](development/phase-2-superseded-ai-instructions-2026-03-25.md) | Phase 2 中已過時的「交給 AI」任務區塊（主線與測試已與現況對齊） |
