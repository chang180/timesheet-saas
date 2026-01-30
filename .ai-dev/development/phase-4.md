# Phase 4：部署、CI/CD 與維運擴充

> 目標：建立可重複、可觀測的部署流程，完成 CI/CD、監控、備援策略，並提供未來擴充方向讓系統在營運階段穩定成長。

## 📊 完成度：0%

**說明**：本階段為部署與維運規劃，尚未開始。

### 前置條件（Phase 3 必須完成項目）

進入 Phase 4 前，建議先完成 Phase 3 以下項目：

- **高優先級**：假期同步功能、匯總報表（公司/單位/部門/小組）、報表匯出（CSV/XLSX）。
- **中優先級**：IP 白名單 middleware（登入與 API 請求檢查）、審計日誌實際記錄、通知與提醒機制（可選）。
- **測試**：組織層級與邀請連結的 Feature Tests / Browser Tests 補齊；CI 可跑過全部測試。

完成後，系統具備完整營運流程，再進行部署與 CI/CD 建置較為合適。

## 1. 部署策略

### 1.1 環境分層
- **環境配置**
  - `development`：本機開發、可使用 Docker Compose。
  - `staging`：與 production 同構，資料庫與 Redis 可共用低階資源。
  - `production`：高可用度部署，建議分離 Web / Queue / Scheduler。
- **基礎架構選項**
  - Kubernetes（建議）：Laravel Octane + Horizon、React 靜態檔案走 CDN。
  - EC2/VM：Nginx + PHP-FPM，搭配 Supervisor 執行 Queue。
  - Vercel / Netlify（前端） + Managed Laravel（後端）。

### 1.2 部署流程
- 後端：
  1. 建置 artifact `php artisan config:cache`, `route:cache`, `event:cache`
  2. 執行資料庫 Migration (`php artisan migrate --force`)
  3. 重新啟動 Queue / Scheduler（或使用 Horizon reload）
- 前端：
  1. `npm ci && npm run build`
  2. 將 `dist/` 上傳至 CDN 或 S3 + CloudFront
  3. 設定 slug 子網域 rewrite（Nginx/CDN）
- 確認 `.env` / secrets 透過 Secret Manager or Vault 統一管理。

**驗收檢查**
- Staging → Production pipeline 一致。
- 可快速 rollback（保留前一次 artifact、使用 feature flag）。

## 2. CI/CD Pipeline

- **推薦工具**：GitHub Actions / GitLab CI
- **工作流程**
  1. `lint-and-test`：`composer test`, `npm run lint`, `npm run test`
  2. `build`：打包 Laravel + 前端 artifact，上傳至 artifact storage
  3. `deploy-staging`：自動部署至 staging，跑 smoke test
  4. `manual-approval`：人工核准後部署 production
- **測試資料處理**
  - Staging 部署後自動執行 seeder（限定測試資料）
  - 匯出/通知 job 放入 sandbox，避免發送給真實使用者

**驗收檢查**
- CI 任務失敗會阻擋 merge，並透過 Slack/Teams 通知。
- Artifact 具備版本號與 git commit hash。

## 3. 監控與可觀測性

- **Logging**
  - Laravel log 至 CloudWatch / Stackdriver
  - 前端錯誤採 Sentry/LogRocket
  - `audit_logs` 提供後台查詢（可另建 BI Dashboard）
- **Metrics**
  - Queue 延遲、匯出任務耗時、API 失敗率（Prometheus + Grafana）
  - 租戶活躍度、週報提交率
- **Alert**
  - 當 Queue 延遲 > 5 分鐘、API 5xx 激增時，發送 Slack/Email 警示
  - 監控政府假期 API 是否失效（透過 cron job 發送成功訊號）

## 4. 資料備援與安全

- **備份**
  - 資料庫每日備份（mysqldump / Percona XtraBackup），保留 30 天
  - 匯出檔案若存於 S3，設定 Lifecycle policy 自動清理
- **災難復原**
  - 訂定 RTO/RPO 目標
  - 定期演練 DB Restoration、Roll-back Flow
- **安全**
  - 定期 Rotate API Key、Slack Webhook
  - 追蹤登入 IP 白名單變更（寫入 audit log + 通知）
  - 若導入 WAF / Cloudflare，需同步更新租戶子網域設定

## 5. 營運支持

- **客服與支援**
  - 提供支援信箱、自動回覆模板
  - 設定租戶公告模組（於歡迎頁顯示系統狀態）
- **教育訓練**
  - 製作操作手冊、影片
  - 舉辦線上說明會（可預約 demo 租戶）
- **計費與合約（若有）**
  - 可擴充 `billing` 模組（Stripe/NewebPay）
  - 追蹤租戶用量（使用者數、匯出次數）

## 6. 未來擴充

- SSO 整合：Azure AD、Google Workspace
- 更細緻的權限控制（以專案或自訂 tag 權限）
- 行動版 UI 與離線草稿儲存
- 數據分析儀表板（與 Looker Studio / Metabase 整合）

## 7. 移交與文件

- 更新 `README` 與 `development/` 文件紀錄最終部署指引、環境差異
- 建立 Runbook（常見問題與處理流程）
- 錄製系統巡覽影片，介紹租戶設定、匯出與通知操作
- 設定內部 Wiki（Confluence/Notion）連結本文件

> 至此，Timesheet SaaS 的核心開發工作完成，後續可依營運需求持續調整與優化。

