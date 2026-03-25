# Agentic Team（多角色開發流程）

本目錄為 **週報通 Timesheet SaaS** 在 AI 協作下的**權威流程說明**：以 **Orchestrator** 為唯一工作入口，搭配四個專責 Agent；**專案負責人**決策，**Cursor 檢視助手**依驗收清單協助複查。  
若與 Cursor Plan 或其他備忘衝突，**以本 repo 之 `.ai-dev` 為準**。

## 閱讀順序

1. [governance/README.md](governance/README.md)（治理模型、誰啟動／誰驗收）
2. [roles/orchestrator.md](roles/orchestrator.md)（派工、合併、待決策報告）
3. 依派工閱讀 [roles/](roles/) 內對應專責角色
4. [templates/decision-report.md](templates/decision-report.md)、[templates/task-package.md](templates/task-package.md)
5. [governance/acceptance-checklist.md](governance/acceptance-checklist.md)（交付給檢視助手時使用）
6. [governance/inspector.md](governance/inspector.md)（Neuron 執行期與 Inspector，可選）

專案根目錄 [AGENTS.md](../../AGENTS.md) 提供給 IDE／工具的簡短入口。

## 目錄結構

| 路徑 | 用途 |
|------|------|
| `roles/orchestrator.md` | 總排程、任務包契約、合併規則、報告章節 |
| `roles/security-agent.md` | 多租戶、認證、IP 白名單、API 暴露 |
| `roles/feature-completeness-agent.md` | spec／phase 對照、落差標記 |
| `roles/test-coverage-agent.md` | Pest、測試範圍、DoD |
| `roles/ux-consistency-agent.md` | Inertia／React／Tailwind／UX |
| `governance/` | 治理、驗收、Inspector、Neuron 後續說明 |
| `templates/` | 待決策報告、任務包範本 |

## 與階段文件的關係

- 階段目標與檢核：`.ai-dev/development/`（[總覽](../development/README.md)）
- 系統規格：`.ai-dev/laravel_weekly_report_spec.md`
- **舊版或已取代段落**：`.ai-dev/archive/`（[封存說明](../archive/README.md)）

## 首次史詩（Epic 01）建議

由 Orchestrator 主導：對齊 `.ai-dev/development` 與程式／測試現況（已完成一輪於 2026-03-25：過時「交給 AI」區塊已封存、Phase 4 前置敘述已更新）。後續史詩請重新拆題並產出新的待決策報告。

## Neuron 與 Inspector

- 僅 **Cursor 多角色對話**、無 Neuron PHP 執行時：報告中「執行觀測」填 **不適用**。
- 已整合 Neuron Agent／Workflow 時：見 [governance/inspector.md](governance/inspector.md)；可執行管線見 [governance/neuron-pipeline-deferred.md](governance/neuron-pipeline-deferred.md)（後續迭代）。
