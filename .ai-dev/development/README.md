# 週報通 Timesheet SaaS 開發階段文件總覽

此目錄彙整從啟動到維運的所有開發階段說明，協助團隊（含不同 AI 助手或實際開發者）快速掌握各階段目標與產出。請依序閱讀對應階段文件：

- `phase-0-1.md`：專案啟動、環境建置、租戶架構與後端基礎實作。（**100% 完成**）
- `phase-2.md`：前端開發、租戶歡迎頁自訂與資料流規劃。（**100% 完成**）
- `phase-3.md`：整合測試、通知機制、報表匯出與安全控制。（**100% 完成**）
- `phase-4.md`：部署流程、CI/CD、維運監控與未來擴充建議。（**0% 完成**；Phase 3 前置已滿足，營運面待建置）

每份文件皆包含：

1. 目標與成功條件
2. 主要工作項目與責任角色
3. 詳細步驟與檢查清單
4. 所需輸入資料／產出成果
5. 風險與待確認事項

## 多角色開發（Agentic Team）

以 **Orchestrator** 為入口的多 Agent 協作流程、模板與驗收方式，見：

- [**`.ai-dev/agentic-team/README.md`**](../agentic-team/README.md)
- 專案根目錄 [**`AGENTS.md`**](../../AGENTS.md)

交付前可搭配 [agentic-team 驗收清單](../agentic-team/governance/acceptance-checklist.md)。

## 文件封存（archive）

被取代之段落或整份快照（可選）置於 [**.ai-dev/archive/**](../archive/README.md)；**作用中文件只維護一份最新版**，詳細規則見該 README。歷史版本一律可透過 **Git** 追溯。

### 已完成的重要里程碑

- 多租戶核心：Company、TenantContext、EnsureTenantScope、slug path 路由。
- 認證：Fortify、Sanctum、2FA、Google OAuth、租戶登入／註冊、邀請接受。
- **HQ Portal**：`api/v1/hq/*`，`hq_admin`＋`EnsureHqAdmin`（middleware 別名 `hq`）；見 `phase-0-1.md`。
- 組織：Division/Department/Team 三層架構、組織層級彈性設定、各層級邀請連結、邀請連結註冊。
- 成員：邀請、角色管理、成員列表；審核 API 預留。
- 週報：CRUD、提交、預覽、預填上週、拖曳排序、工時統計、表單驗證與 UX 優化。
- 設定：歡迎頁、IP 白名單（UI + middleware）、租戶品牌。
- Phase 3：假期同步（HolidaySyncService、HolidayCacheService、API）、匯總報表與匯出（CSV/XLSX）、通知與排程、IP 白名單 middleware、審計日誌、Rate limiting、週報 Reopen。
- 測試：以本機 `php artisan test --compact` 為準（通過筆數隨版本變動）；Phase 3 驗證說明見 `phase-3-verification-report.md`。

### 下一步建議

1. 進入 Phase 4：部署流程、CI/CD、維運監控與備援。
2. 視需求規劃 Redmine/Jira 整合（可選）。

> **工作流程建議**：完成每階段前，先於專案任務系統或 GitHub Issue 建立對應任務，並在文件中勾選或更新狀態。階段交付前，請將成果與遇到的阻礙回填到文件的「檢查與驗收」段落。多 Agent 聯合作業請沿用 **`agentic-team`** 流程。
