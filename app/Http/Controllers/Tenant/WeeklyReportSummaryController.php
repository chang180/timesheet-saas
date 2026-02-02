<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Services\AuditService;
use App\Services\WeeklyReportExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyReportSummaryController extends Controller
{
    public function __construct(
        private WeeklyReportExportService $exportService
    ) {}

    /**
     * 取得週報匯總資料或匯出檔案
     */
    public function index(Request $request, Company $company): JsonResponse|StreamedResponse
    {
        $user = $request->user();
        $this->authorize('export', WeeklyReport::class);

        $exportFormat = $request->input('export');

        if ($exportFormat && in_array($exportFormat, ['csv', 'xlsx'], true)) {
            return $this->handleExport($request, $company, $user, $exportFormat);
        }

        $year = (int) $request->input('year', now()->isoWeekYear());
        $week = $request->input('week');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $divisionId = $request->input('division_id');
        $departmentId = $request->input('department_id');
        $teamId = $request->input('team_id');

        $query = WeeklyReport::query()
            ->where('company_id', $company->id)
            ->with(['user:id,name,email,division_id,department_id,team_id', 'items']);

        $this->applyTimeFilter($query, $year, $week, $startDate, $endDate);
        $this->applyHierarchyFilter($query, $user, $divisionId, $departmentId, $teamId);

        $reports = $query->get();

        $summary = $this->buildSummary($reports, $company, $divisionId, $departmentId, $teamId);

        return response()->json([
            'data' => $summary,
            'filters' => [
                'year' => $year,
                'week' => $week,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'division_id' => $divisionId,
                'department_id' => $departmentId,
                'team_id' => $teamId,
            ],
        ]);
    }

    /**
     * 處理匯出請求
     */
    private function handleExport(Request $request, Company $company, User $user, string $format): StreamedResponse
    {
        $filters = [
            'year' => $request->input('year', now()->isoWeekYear()),
            'week' => $request->input('week'),
            'division_id' => $request->input('division_id'),
            'department_id' => $request->input('department_id'),
            'team_id' => $request->input('team_id'),
        ];

        $filename = $this->exportService->generateFilename($company, $filters, $format);

        AuditService::exported(
            $company,
            "匯出週報資料為 {$format} 格式",
            ['format' => $format, 'filters' => $filters]
        );

        if ($format === 'csv') {
            $content = $this->exportService->exportCsv($company, $user, $filters);

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $path = $this->exportService->exportXlsx($company, $user, $filters);

        return response()->streamDownload(function () use ($path) {
            readfile($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * 套用時間範圍篩選
     */
    private function applyTimeFilter(
        Builder $query,
        int $year,
        ?string $week,
        ?string $startDate,
        ?string $endDate
    ): void {
        if ($startDate && $endDate) {
            $query->whereHas('items', function (Builder $q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });

            return;
        }

        if ($week) {
            $query->where('work_year', $year)->where('work_week', (int) $week);

            return;
        }

        $query->where('work_year', $year);
    }

    /**
     * 套用組織層級篩選（並根據使用者權限限制）
     */
    private function applyHierarchyFilter(
        Builder $query,
        User $user,
        ?string $divisionId,
        ?string $departmentId,
        ?string $teamId
    ): void {
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
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
            if ($teamId) {
                $query->where('team_id', $teamId);
            }

            return;
        }

        if ($user->hasRole('department_manager')) {
            $query->where('department_id', $user->department_id);
            if ($teamId) {
                $query->where('team_id', $teamId);
            }

            return;
        }

        if ($user->hasRole('team_lead')) {
            $query->where('team_id', $user->team_id);

            return;
        }

        $query->where('user_id', $user->id);
    }

    /**
     * 建立匯總資料
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, WeeklyReport>  $reports
     * @return array<string, mixed>
     */
    private function buildSummary(
        \Illuminate\Database\Eloquent\Collection $reports,
        Company $company,
        ?string $divisionId,
        ?string $departmentId,
        ?string $teamId
    ): array {
        $totalHours = 0;
        $billableHours = 0;
        $reportCount = $reports->count();
        $submittedCount = 0;
        $draftCount = 0;

        $memberSummaries = [];

        foreach ($reports as $report) {
            if ($report->status === WeeklyReport::STATUS_SUBMITTED) {
                $submittedCount++;
            } else {
                $draftCount++;
            }

            $reportHours = 0;
            $reportBillableHours = 0;

            foreach ($report->items as $item) {
                if ($item->type === WeeklyReportItem::TYPE_CURRENT_WEEK) {
                    $reportHours += $item->hours_spent ?? 0;
                    if ($item->is_billable) {
                        $reportBillableHours += $item->hours_spent ?? 0;
                    }
                }
            }

            $totalHours += $reportHours;
            $billableHours += $reportBillableHours;

            $userId = $report->user_id;
            if (! isset($memberSummaries[$userId])) {
                $memberSummaries[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $report->user?->name ?? 'Unknown',
                    'user_email' => $report->user?->email ?? '',
                    'total_hours' => 0,
                    'billable_hours' => 0,
                    'report_count' => 0,
                    'reports' => [],
                ];
            }

            $memberSummaries[$userId]['total_hours'] += $reportHours;
            $memberSummaries[$userId]['billable_hours'] += $reportBillableHours;
            $memberSummaries[$userId]['report_count']++;
            $memberSummaries[$userId]['reports'][] = [
                'id' => $report->id,
                'work_year' => $report->work_year,
                'work_week' => $report->work_week,
                'status' => $report->status,
                'hours' => $reportHours,
                'billable_hours' => $reportBillableHours,
                'summary' => $report->summary,
            ];
        }

        $level = $this->determineLevel($divisionId, $departmentId, $teamId);

        return [
            'level' => $level,
            'company_id' => $company->id,
            'company_name' => $company->name,
            'total_hours' => round($totalHours, 2),
            'billable_hours' => round($billableHours, 2),
            'report_count' => $reportCount,
            'submitted_count' => $submittedCount,
            'draft_count' => $draftCount,
            'member_count' => count($memberSummaries),
            'members' => array_values($memberSummaries),
        ];
    }

    /**
     * 判斷匯總層級
     */
    private function determineLevel(?string $divisionId, ?string $departmentId, ?string $teamId): string
    {
        if ($teamId) {
            return 'team';
        }
        if ($departmentId) {
            return 'department';
        }
        if ($divisionId) {
            return 'division';
        }

        return 'company';
    }
}
