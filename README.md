# Timesheet SaaS

多租戶工作週報管理系統，使用 Laravel 12 與 React 建置，用於追蹤團隊工作時數並產生報表。

## 技術棧

- **後端**: Laravel 12 + PHP 8.2+
- **前端**: React 19 + TypeScript + Inertia.js
- **認證**: Laravel Fortify + Sanctum
- **樣式**: Tailwind CSS 4
- **測試**: Pest 4

## 安裝

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## 開發

```bash
composer run dev
```

## 詳細開發說明

請參考 [`.ai-dev/laravel_weekly_report_spec.md`](.ai-dev/laravel_weekly_report_spec.md) 了解完整的系統設計與開發規範。

