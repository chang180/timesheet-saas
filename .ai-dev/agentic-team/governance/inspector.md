# Neuron Inspector（可觀測性）

當 **Neuron** 於應用程式內實際執行 Agent／Workflow 時，可透過 **Inspector** 在儀表板檢視步驟、tool 呼叫與流程時間軸。

## 設定

1. 於 [app.inspector.dev](https://app.inspector.dev) 註冊並建立 **ingestion key**。
2. 在應用程式環境中設定（勿提交真實金鑰至版本庫）：

```env
INSPECTOR_INGESTION_KEY=your_key_here
```

3. 依 Neuron 版本將金鑰讀入應用程式設定（僅在 `config/` 內使用 `env()`，業務程式用 `config()`）。

官方說明：

- [Monitoring & Debugging (v2)](https://docs.neuron-ai.dev/v2/the-basics/observability)
- [Monitoring & Debugging (agent)](https://docs.neuron-ai.dev/agent/observability)

## 與本專案流程的關係

| 情境 | Inspector |
|------|-----------|
| 僅 Cursor 對話扮演 Orchestrator／專責角色 | **不適用**（無 Neuron runtime 遙测） |
| Laravel 內執行 Neuron Agent／Workflow | **建議啟用**；Orchestrator 於待決策報告填「執行觀測」 |

## 待決策報告中的「執行觀測」欄位

Orchestrator 應簡述：

- 執行時間（約略 UTC 或本地時區）
- 於 Inspector 中可辨識的敘述（例如 trace 標題、主要 tool 名稱）
- 若無法取得連結，至少說明已啟用 ingestion 並可供人工於儀表板搜尋

純文件／Cursor 流程：該欄填 **「不適用（無 Neuron 執行）」**。
