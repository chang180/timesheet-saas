# Google OAuth 設定指引

本文件將引導您如何在 Google Cloud Platform (GCP) 上設定 OAuth 2.0，以啟用 Google 帳號登入/註冊功能。

## 前置需求

- Google 帳號
- 已部署的應用程式（用於設定授權的重新導向 URI）

## 設定步驟

### 1. 建立 Google Cloud 專案

1. 前往 [Google Cloud Console](https://console.cloud.google.com/)
2. 點擊頂部專案選擇器，然後點擊「**新增專案**」
3. 輸入專案名稱（例如：`Timesheet SaaS`）
4. 點擊「**建立**」
5. 等待專案建立完成後，選擇該專案

### 2. 啟用 Google Identity API

1. 在 Google Cloud Console 中，前往「**API 和服務**」>「**程式庫**」
2. 搜尋「**Google Identity**」或「**Google+ API**」
3. 選擇「**Google Identity**」或「**Google+ API**」
4. 點擊「**啟用**」

> **注意**：Google 已將 Google+ API 整合到 Google Identity 服務中。建議使用「Google Identity」API。

### 3. 建立 OAuth 2.0 憑證

1. 前往「**API 和服務**」>「**憑證**」
2. 點擊「**建立憑證**」>「**OAuth 用戶端 ID**」
3. 如果這是第一次建立，系統會要求您設定「**OAuth 同意畫面**」：
   - **使用者類型**：選擇「**外部**」（除非您使用 Google Workspace）
   - **應用程式名稱**：輸入您的應用程式名稱（例如：`週報通 Timesheet SaaS`）
   - **使用者支援電子郵件**：選擇您的電子郵件
   - **應用程式網域**：輸入您的網域（例如：`timesheet-saas.test`）
   - **開發人員連絡資訊**：輸入您的電子郵件
   - 點擊「**儲存並繼續**」
   - 在「**範圍**」頁面，點擊「**儲存並繼續**」（我們只需要基本資訊）
   - 在「**測試使用者**」頁面，可以新增測試使用者（可選）
   - 點擊「**返回資訊主頁**」

4. 回到「**建立 OAuth 用戶端 ID**」頁面：
   - **應用程式類型**：選擇「**網頁應用程式**」
   - **名稱**：輸入憑證名稱（例如：`Timesheet SaaS Web Client`）

### 4. 設定授權的重新導向 URI

在「**已授權的重新導向 URI**」區塊中，新增以下 URI：

#### 本機開發環境（Laravel Herd）

```
http://timesheet-saas.test/auth/google/callback
https://timesheet-saas.test/auth/google/callback
```

#### 正式環境

```
https://your-domain.com/auth/google/callback
```

> **重要**：
> - 必須包含完整的網域和路徑
> - 必須包含 `http://` 或 `https://` 前綴
> - 路徑必須完全匹配：`/auth/google/callback`
> - 如果使用 HTTPS，請確保您的網域有有效的 SSL 憑證

### 5. 取得 Client ID 和 Client Secret

1. 建立憑證後，系統會顯示「**OAuth 用戶端已建立**」對話框
2. 複製「**用戶端 ID**」和「**用戶端密鑰**」
3. 如果關閉了對話框，可以在「**憑證**」頁面中找到：
   - 點擊您剛建立的 OAuth 用戶端 ID
   - 複製「**用戶端 ID**」和「**用戶端密鑰**」

### 6. 設定環境變數

在您的 Laravel 專案的 `.env` 檔案中，新增以下環境變數：

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=/auth/google/callback
```

> **注意**：
> - `GOOGLE_REDIRECT_URI` 預設為 `/auth/google/callback`，通常不需要修改
> - 如果您的應用程式使用不同的路徑，請相應調整

### 7. 驗證設定

1. 確保您的應用程式已部署並可存取
2. 前往註冊頁面
3. 點擊「**使用 Google 註冊**」按鈕
4. 應該會重定向到 Google 登入頁面
5. 登入後，應該會重定向回您的應用程式

## 疑難排解

### 錯誤：`redirect_uri_mismatch`

**原因**：重新導向 URI 與 GCP 中設定的不一致

**解決方法**：
1. 檢查 `.env` 中的 `GOOGLE_REDIRECT_URI` 是否正確
2. 檢查 GCP 中的「**已授權的重新導向 URI**」是否包含正確的 URI
3. 確保 URI 完全匹配（包括 `http://` 或 `https://`、網域、路徑）

### 錯誤：`invalid_client`

**原因**：Client ID 或 Client Secret 不正確

**解決方法**：
1. 檢查 `.env` 中的 `GOOGLE_CLIENT_ID` 和 `GOOGLE_CLIENT_SECRET` 是否正確
2. 確保沒有多餘的空格或換行
3. 重新複製 GCP 中的憑證資訊

### 錯誤：`access_denied`

**原因**：使用者拒絕授權或應用程式未通過 Google 驗證

**解決方法**：
1. 確保 OAuth 同意畫面已正確設定
2. 如果應用程式處於測試模式，確保使用者的 Google 帳號已加入測試使用者清單
3. 檢查應用程式的狀態（測試模式 vs 已發布）

### 本機開發環境（.test 網域）無法使用

**原因**：Google OAuth 不支援 `.test` 等本機網域

**解決方法**：
1. 使用 `localhost` 或實際的網域進行開發
2. 在 GCP 中新增 `http://localhost:8000/auth/google/callback` 到授權的重新導向 URI
3. 或使用 ngrok 等工具建立臨時的公開 URL

## 安全性建議

1. **保護 Client Secret**：
   - 永遠不要將 Client Secret 提交到版本控制系統
   - 使用環境變數儲存敏感資訊
   - 定期輪換 Client Secret

2. **限制重新導向 URI**：
   - 只新增必要的重新導向 URI
   - 使用 HTTPS（在正式環境中）

3. **監控使用情況**：
   - 定期檢查 GCP 中的 API 使用情況
   - 設定配額限制以防止濫用

## 相關資源

- [Google Identity 文件](https://developers.google.com/identity)
- [OAuth 2.0 最佳實踐](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Laravel Socialite 文件](https://laravel.com/docs/socialite)
