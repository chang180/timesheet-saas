# ELK MCP Server

這是提供給 Cursor MCP 環境使用的 ELK Stack MCP Server，讓您可以在 Cursor 中直接查詢和分析 Laravel 應用程式的日誌。

## 安裝

```bash
cd docker/elk/mcp-server
npm install
```

## 配置

MCP Server 已經在 `.mcp.json` 中配置完成。確保以下環境變數正確：

- `ELASTICSEARCH_HOST`: Elasticsearch 主機地址（預設: http://localhost:9200）
- `ELASTICSEARCH_INDEX_PATTERN`: 索引模式（預設: laravel-logs-*）

## 使用

1. 確保 ELK Stack 已啟動：
   ```bash
   docker-compose -f docker/elk/docker-compose-elk.yml up -d
   ```

2. 在 Cursor 中，MCP Server 會自動啟動並提供以下工具：
   - `search_logs`: 搜尋日誌
   - `filter_logs`: 過濾日誌
   - `get_recent_errors`: 取得最近的錯誤
   - `get_log_statistics`: 取得日誌統計

## 工具說明

### search_logs
搜尋 Laravel 應用程式日誌，支援：
- 文字搜尋（在訊息、例外訊息和堆疊追蹤中）
- 日誌級別過濾
- 時間範圍查詢

### filter_logs
根據多個條件過濾日誌：
- 日誌級別
- 環境
- 用戶 ID
- 請求 ID
- 時間範圍

### get_recent_errors
取得最近的錯誤日誌（預設過去 24 小時）

### get_log_statistics
取得日誌統計資料：
- 各級別日誌數量
- 環境分布
- 時間分布
