# ELK Stack 設定說明

此 ELK Stack 配置專為 Laravel 專案設計，用於收集、解析和視覺化應用程式日誌。

## 與開發環境的相容性

### ✅ 與 Laravel Sail 相容

- **獨立 Network**: 使用 `elk-network`，不會與 Sail 的 network 衝突
- **獨立容器名稱**: 所有容器使用 `elk-` 前綴，避免與 Sail 容器衝突
- **端口不衝突**: ELK 使用的端口（9200, 9300, 5044, 5601, 9600）不會與 Sail 的預設端口（80, 3306, 6379 等）衝突
- **獨立 Volume**: 使用獨立的 volume 名稱，避免與 Sail 的 volume 衝突

### ✅ 與 Laravel Herd 相容

- **完全獨立**: Herd 使用本地 PHP 環境，不依賴 Docker，因此完全不會衝突
- **日誌讀取**: Filebeat 直接讀取 `storage/logs` 目錄，無論應用程式是透過 Herd 還是 Sail 運行

## 啟動 ELK Stack

```bash
# 從專案根目錄執行，使用 -f 指定檔案
docker-compose -f docker/elk/docker-compose-elk.yml up -d

# 或先切換到目錄
cd docker/elk
docker-compose -f docker-compose-elk.yml up -d
```

## 停止 ELK Stack

```bash
# 從專案根目錄執行
docker-compose -f docker/elk/docker-compose-elk.yml down

# 如需同時刪除 volumes（會清除 Elasticsearch 資料）
docker-compose -f docker/elk/docker-compose-elk.yml down -v
```

## 訪問服務

- **Kibana**: http://localhost:5601
- **Elasticsearch**: http://localhost:9200
- **Logstash Monitoring**: http://localhost:19600

## 檢查服務狀態

```bash
docker-compose -f docker/elk/docker-compose-elk.yml ps
```

## 查看日誌

```bash
# 查看所有服務日誌
docker-compose -f docker/elk/docker-compose-elk.yml logs -f

# 查看特定服務日誌
docker-compose -f docker/elk/docker-compose-elk.yml logs -f filebeat
docker-compose -f docker/elk/docker-compose-elk.yml logs -f logstash
```

## 與 Sail 同時使用

ELK Stack 和 Sail 可以同時運行，互不干擾：

```bash
# 啟動 Sail（如果使用）
./vendor/bin/sail up -d

# 啟動 ELK Stack（使用 -f 指定檔案）
docker-compose -f docker/elk/docker-compose-elk.yml up -d
```

## 與 Herd 同時使用

Herd 和 ELK Stack 完全獨立，可以同時使用：

```bash
# Herd 自動管理 PHP 和應用程式
# 只需啟動 ELK Stack（使用 -f 指定檔案）
docker-compose -f docker/elk/docker-compose-elk.yml up -d
```

## 日誌收集

Filebeat 會自動監控以下日誌檔案：
- `storage/logs/laravel.log`
- `storage/logs/laravel-YYYY-MM-DD.log` (每日輪轉檔案)

## 資源需求

- **Elasticsearch**: 至少 512MB 記憶體（建議 1GB+）
- **Logstash**: 256MB 記憶體
- **Kibana**: 約 512MB 記憶體
- **Filebeat**: 約 50MB 記憶體

總計約需要 **1.5-2GB** 可用記憶體。

## 疑難排解

### 端口已被占用

如果遇到端口衝突，可以修改 `docker-compose-elk.yml` 中的端口映射：

```yaml
ports:
  - "19200:9200"  # 改為其他端口
```

### Windows 路徑問題

如果遇到 volume 掛載問題，確保使用正確的路徑格式。在 Windows 上，可能需要使用絕對路徑。

### Elasticsearch 啟動失敗

檢查是否有足夠的記憶體：

```bash
# 檢查容器日誌
docker logs elk-elasticsearch
```

### Filebeat 無法讀取日誌

確保 `storage/logs` 目錄存在且有讀取權限：

```bash
# 檢查目錄
ls -la storage/logs

# 檢查 Filebeat 日誌
docker logs elk-filebeat
```

## 生產環境注意事項

⚠️ **此配置僅適用於開發環境**

生產環境需要：
1. 啟用 Elasticsearch 和 Kibana 的安全認證
2. 配置適當的資源限制
3. 設定日誌保留策略
4. 考慮使用 Elastic Cloud 或託管服務
