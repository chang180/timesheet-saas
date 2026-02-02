<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use League\Csv\Writer;
use Spatie\SimpleExcel\SimpleExcelWriter;

class WeeklyReportExportService
{
    /**
     * 匯出週報為 CSV 格式
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(Company $company, User $user, array $filters): string
    {
        $reports = $this->getReports($company, $user, $filters);
        $rows = $this->transformToRows($reports);

        $csv = Writer::createFromString();
        $csv->insertOne($this->getHeaders());

        foreach ($rows as $row) {
            $csv->insertOne($row);
        }

        return $csv->toString();
    }

    /**
     * 匯出週報為 XLSX 格式
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportXlsx(Company $company, User $user, array $filters): string
    {
        $reports = $this->getReports($company, $user, $filters);
        $rows = $this->transformToRows($reports);

        $filename = $this->generateFilename($company, $filters, 'xlsx');
        $path = storage_path("app/exports/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = SimpleExcelWriter::create($path);
        $writer->addHeader($this->getHeaders());

        foreach ($rows as $row) {
            $writer->addRow($this->rowToAssoc($row));
        }

        $writer->close();

        return $path;
    }

    /**
     * 產生匯出檔名
     *
     * @param  array<string, mixed>  $filters
     */
    public function generateFilename(Company $company, array $filters, string $extension): string
    {
        $level = $this->determineLevel($filters);
        $yearWeek = $this->getYearWeekString($filters);
        $timestamp = now()->format('YmdHis');

        return "{$company->slug}-{$level}-{$yearWeek}-{$timestamp}.{$extension}";
    }

    /**
     * 取得週報資料
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, WeeklyReport>
     */
    private function getReports(Company $company, User $user, array $filters): Collection
    {
        $query = WeeklyReport::query()
            ->where('company_id', $company->id)
            ->with(['user:id,name,email,division_id,department_id,team_id', 'items', 'division:id,name', 'department:id,name', 'team:id,name']);

        $this->applyTimeFilter($query, $filters);
        $this->applyHierarchyFilter($query, $user, $filters);

        return $query->get();
    }

    /**
     * 套用時間篩選
     *
     * @param  Builder<WeeklyReport>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyTimeFilter(Builder $query, array $filters): void
    {
        $year = (int) ($filters['year'] ?? now()->isoWeekYear());
        $week = $filters['week'] ?? null;

        if ($week) {
            $query->where('work_year', $year)->where('work_week', (int) $week);

            return;
        }

        $query->where('work_year', $year);
    }

    /**
     * 套用組織層級篩選
     *
     * @param  Builder<WeeklyReport>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyHierarchyFilter(Builder $query, User $user, array $filters): void
    {
        $divisionId = $filters['division_id'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $teamId = $filters['team_id'] ?? null;

        if ($user->isCompanyAdmin()) {
            if ($divisionId) {
                $query->where('division_id', $divisionId);
            }
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
            if ($teamId) {
                $query->where('team_id', $teamId);
            }

            return;
        }

        if ($user->hasRole('division_lead')) {
            $query->where('division_id', $user->division_id);

            return;
        }

        if ($user->hasRole('department_manager')) {
            $query->where('department_id', $user->department_id);

            return;
        }

        if ($user->hasRole('team_lead')) {
            $query->where('team_id', $user->team_id);

            return;
        }

        $query->where('user_id', $user->id);
    }

    /**
     * 取得欄位標題
     *
     * @return list<string>
     */
    private function getHeaders(): array
    {
        return [
            '成員',
            '信箱',
            '單位',
            '部門',
            '小組',
            '年',
            '週',
            '狀態',
            '工作項目',
            '內容',
            '實際工時',
            '預計工時',
            '計費',
            '問題編號',
            '標籤',
            '類型',
        ];
    }

    /**
     * 轉換報表為匯出列
     *
     * @param  Collection<int, WeeklyReport>  $reports
     * @return list<list<string|float|null>>
     */
    private function transformToRows(Collection $reports): array
    {
        $rows = [];

        foreach ($reports as $report) {
            foreach ($report->items as $item) {
                $rows[] = [
                    $report->user?->name ?? '',
                    $report->user?->email ?? '',
                    $report->division?->name ?? '',
                    $report->department?->name ?? '',
                    $report->team?->name ?? '',
                    $report->work_year,
                    $report->work_week,
                    $this->translateStatus($report->status),
                    $item->title ?? '',
                    $item->content ?? '',
                    $item->hours_spent ?? 0,
                    $item->planned_hours ?? 0,
                    $item->is_billable ? '是' : '否',
                    $item->issue_reference ?? '',
                    is_array($item->tags) ? implode(', ', $item->tags) : '',
                    $this->translateType($item->type),
                ];
            }
        }

        return $rows;
    }

    /**
     * 將列轉換為關聯陣列（供 SimpleExcel 使用）
     *
     * @param  list<string|float|null>  $row
     * @return array<string, string|float|null>
     */
    private function rowToAssoc(array $row): array
    {
        $headers = $this->getHeaders();
        $assoc = [];

        foreach ($headers as $index => $header) {
            $assoc[$header] = $row[$index] ?? '';
        }

        return $assoc;
    }

    /**
     * 判斷匯總層級
     *
     * @param  array<string, mixed>  $filters
     */
    private function determineLevel(array $filters): string
    {
        if (! empty($filters['team_id'])) {
            return 'team';
        }
        if (! empty($filters['department_id'])) {
            return 'department';
        }
        if (! empty($filters['division_id'])) {
            return 'division';
        }

        return 'company';
    }

    /**
     * 取得年週字串
     *
     * @param  array<string, mixed>  $filters
     */
    private function getYearWeekString(array $filters): string
    {
        $year = $filters['year'] ?? now()->isoWeekYear();
        $week = $filters['week'] ?? null;

        if ($week) {
            return sprintf('%d%02d', $year, $week);
        }

        return (string) $year;
    }

    /**
     * 翻譯狀態
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            WeeklyReport::STATUS_DRAFT => '草稿',
            WeeklyReport::STATUS_SUBMITTED => '已送出',
            WeeklyReport::STATUS_LOCKED => '已鎖定',
            default => $status,
        };
    }

    /**
     * 翻譯類型
     */
    private function translateType(string $type): string
    {
        return match ($type) {
            WeeklyReportItem::TYPE_CURRENT_WEEK => '本週工作',
            WeeklyReportItem::TYPE_NEXT_WEEK => '下週計畫',
            default => $type,
        };
    }
}
