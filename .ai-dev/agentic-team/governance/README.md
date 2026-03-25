# 治理（Governance）

## 參與方

| 參與方 | 職責 |
|--------|------|
| **專案負責人（人類）** | 提供目標與優先級；閱讀 Orchestrator 之**待決策報告**並做最終裁決；核准進入下一史詩。 |
| **Orchestrator** | 唯一工作入口：拆題、下發任務包、收集四專責輸出、合併敘述、產出待決策報告與下一輪建議。規格：[../roles/orchestrator.md](../roles/orchestrator.md)。 |
| **SecurityAgent 等** | 在任務包範圍內審查或實作，依規格格式回覆；不自行擴張範圍或跳過合併。 |
| **Cursor 檢視助手** | 於階段或 PR 後依 [acceptance-checklist.md](acceptance-checklist.md) 協助複查；**不**代替 Orchestrator 拆題。 |

## 產出物建議存放

- **待決策報告**：可置於 PR 描述、Issue 留言，或 `reports/`（若專案新增目錄，由 Orchestrator 在該史詩定義）。
- **任務包**：Orchestrator 在對話或文件中填寫 [../templates/task-package.md](../templates/task-package.md)。

## 相關文件

- [acceptance-checklist.md](acceptance-checklist.md)
- [inspector.md](inspector.md)
- [neuron-pipeline-deferred.md](neuron-pipeline-deferred.md)
