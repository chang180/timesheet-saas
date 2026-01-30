<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeeklyReportUpsertRequest;
use App\Models\Company;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyReportController extends Controller
{
    public function index(Request $request, Company $company): Response|RedirectResponse
    {
        $user = $request->user();
        $this->assertBelongsToCompany($user->company_id ?? null, $company);

        if ($redirect = $this->ensureOnboarded($company, $user->role ?? null)) {
            return $redirect;
        }

        // 取得篩選參數
        $filterYear = $request->input('filter_year');
        $filterStatus = $request->input('filter_status');

        $query = WeeklyReport::query()
            ->with(['items'])
            ->where('company_id', $company->id)
            ->where('user_id', $user->id);

        // 套用年度篩選
        if ($filterYear && $filterYear !== 'all') {
            $query->where('work_year', (int) $filterYear);
        }

        // 套用狀態篩選
        if ($filterStatus && $filterStatus !== 'all') {
            $query->where('status', $filterStatus);
        }

        $reports = $query
            ->orderByDesc('work_year')
            ->orderByDesc('work_week')
            ->get()
            ->map(fn (WeeklyReport $report) => [
                'id' => $report->id,
                'workYear' => $report->work_year,
                'workWeek' => $report->work_week,
                'status' => $report->status,
                'summary' => $report->summary,
                'totalHours' => $report->items->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)->sum('hours_spent'),
                'createdAt' => $report->created_at?->toIso8601String(),
                'updatedAt' => $report->updated_at?->toIso8601String(),
            ]);

        $currentWeek = CarbonImmutable::now($company->timezone ?? config('app.timezone'))
            ->startOfWeek();

        $currentYear = (int) $currentWeek->isoFormat('GGGG');
        $currentWeekNumber = (int) $currentWeek->isoWeek();

        $weekDateRange = $this->getWeekDateRange(
            $currentYear,
            $currentWeekNumber,
            $company->timezone ?? config('app.timezone'),
        );

        $missingWeeks = $this->calculateMissingWeeks(
            $reports->toArray(),
            $currentYear,
            $currentWeekNumber,
            $company->timezone ?? config('app.timezone'),
        );

        // 計算可用的年份選項（從最早的週報到當前年）
        $availableYears = WeeklyReport::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->selectRaw('DISTINCT work_year')
            ->orderByDesc('work_year')
            ->pluck('work_year')
            ->values()
            ->all();

        // 確保當前年度在選項中
        if (! in_array($currentYear, $availableYears)) {
            array_unshift($availableYears, $currentYear);
        }

        return Inertia::render('weekly/list', [
            'reports' => $reports,
            'company' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'name' => $company->name,
            ],
            'defaults' => [
                'year' => $currentYear,
                'week' => $currentWeekNumber,
            ],
            'weekDateRange' => $weekDateRange,
            'missingWeeks' => $missingWeeks,
            'filters' => [
                'year' => $filterYear ?? 'all',
                'status' => $filterStatus ?? 'all',
            ],
            'availableYears' => $availableYears,
        ]);
    }

    public function create(Request $request, Company $company): Response|RedirectResponse
    {
        $user = $request->user();
        $this->assertBelongsToCompany($user->company_id ?? null, $company);

        if ($redirect = $this->ensureOnboarded($company, $user->role ?? null)) {
            return $redirect;
        }

        [$year, $week] = $this->resolveWeekFromRequest($request, $company);

        $existing = WeeklyReport::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->where('work_year', $year)
            ->where('work_week', $week)
            ->first();

        if ($existing) {
            return redirect()->route('tenant.weekly-reports.edit', [$company, $existing])
                ->with('info', '當週週報已存在，為你開啟編輯畫面。');
        }

        $prefill = $this->prefillFromPreviousWeek($company, $user->id, $year, $week);

        $weekDateRange = $this->getWeekDateRange(
            $year,
            $week,
            $company->timezone ?? config('app.timezone'),
        );

        // 計算下一週的日期範圍
        $nextWeekDate = CarbonImmutable::now($company->timezone ?? config('app.timezone'))
            ->setISODate($year, $week)
            ->addWeek();
        $nextWeekYear = (int) $nextWeekDate->isoFormat('GGGG');
        $nextWeekNumber = (int) $nextWeekDate->isoWeek();
        $nextWeekDateRange = $this->getWeekDateRange(
            $nextWeekYear,
            $nextWeekNumber,
            $company->timezone ?? config('app.timezone'),
        );

        return Inertia::render('weekly/form', [
            'mode' => 'create',
            'report' => null,
            'company' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'name' => $company->name,
            ],
            'defaults' => [
                'year' => $year,
                'week' => $week,
            ],
            'weekDateRange' => $weekDateRange,
            'nextWeekDateRange' => $nextWeekDateRange,
            'prefill' => $prefill,
        ]);
    }

    public function store(WeeklyReportUpsertRequest $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        [$year, $week] = [$request->integer('work_year'), $request->integer('work_week')];

        $existing = WeeklyReport::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->where('work_year', $year)
            ->where('work_week', $week)
            ->first();

        if ($existing) {
            return redirect()
                ->route('tenant.weekly-reports.edit', [$company, $existing])
                ->with('info', '當週週報已存在，為你開啟編輯畫面。');
        }

        $payload = $request->payload();

        /** @var WeeklyReport $report */
        $report = WeeklyReport::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'department_id' => $user->department_id,
            'team_id' => $user->team_id,
            'work_year' => $payload['work_year'],
            'work_week' => $payload['work_week'],
            'summary' => $payload['summary'],
            'metadata' => $payload['metadata'],
            'status' => WeeklyReport::STATUS_DRAFT,
        ]);

        $this->syncItems($report, $payload['current_week'], $payload['next_week']);

        return redirect()
            ->route('tenant.weekly-reports.edit', [$company, $report])
            ->with('success', '已建立週報草稿，可繼續編輯。');
    }

    public function edit(Request $request, Company $company, WeeklyReport $weeklyReport): Response|RedirectResponse
    {
        $user = $request->user();
        $this->assertBelongsToCompany($user->company_id ?? null, $company);

        if ($weeklyReport->user_id !== $user->id) {
            abort(403, '無權限存取此週報');
        }

        if ($redirect = $this->ensureOnboarded($company, $user->role ?? null)) {
            return $redirect;
        }

        $weeklyReport->load('items');

        $weekDateRange = $this->getWeekDateRange(
            $weeklyReport->work_year,
            $weeklyReport->work_week,
            $company->timezone ?? config('app.timezone'),
        );

        // 計算下一週的日期範圍
        $nextWeekDate = CarbonImmutable::now($company->timezone ?? config('app.timezone'))
            ->setISODate($weeklyReport->work_year, $weeklyReport->work_week)
            ->addWeek();
        $nextWeekYear = (int) $nextWeekDate->isoFormat('GGGG');
        $nextWeekNumber = (int) $nextWeekDate->isoWeek();
        $nextWeekDateRange = $this->getWeekDateRange(
            $nextWeekYear,
            $nextWeekNumber,
            $company->timezone ?? config('app.timezone'),
        );

        return Inertia::render('weekly/form', [
            'mode' => 'edit',
            'report' => [
                'id' => $weeklyReport->id,
                'workYear' => $weeklyReport->work_year,
                'workWeek' => $weeklyReport->work_week,
                'status' => $weeklyReport->status,
                'summary' => $weeklyReport->summary,
                'currentWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item) => $this->transformCurrentItem($item)),
                'nextWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item) => $this->transformNextItem($item)),
            ],
            'company' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'name' => $company->name,
            ],
            'defaults' => [
                'year' => $weeklyReport->work_year,
                'week' => $weeklyReport->work_week,
            ],
            'weekDateRange' => $weekDateRange,
            'nextWeekDateRange' => $nextWeekDateRange,
            'prefill' => [
                'currentWeek' => [],
                'nextWeek' => [],
            ],
            'canReopen' => $user->can('reopen', $weeklyReport),
        ]);
    }

    public function update(WeeklyReportUpsertRequest $request, Company $company, WeeklyReport $weeklyReport): RedirectResponse
    {
        $user = $request->user();

        if ($weeklyReport->user_id !== $user->id) {
            abort(403, '無權限存取此週報');
        }

        $payload = $request->payload();

        $weeklyReport->forceFill([
            'work_year' => $payload['work_year'],
            'work_week' => $payload['work_week'],
            'summary' => $payload['summary'],
            'metadata' => $payload['metadata'],
        ])->save();

        $this->syncItems($weeklyReport, $payload['current_week'], $payload['next_week']);

        return redirect()
            ->route('tenant.weekly-reports.edit', [$company, $weeklyReport])
            ->with('success', '週報已更新。');
    }

    public function submit(Request $request, Company $company, WeeklyReport $weeklyReport): RedirectResponse
    {
        $user = $request->user();
        $this->assertBelongsToCompany($user->company_id ?? null, $company);

        if ($weeklyReport->user_id !== $user->id) {
            abort(403, '無權限存取此週報');
        }

        if ($weeklyReport->status !== WeeklyReport::STATUS_DRAFT) {
            return redirect()
                ->route('tenant.weekly-reports.edit', [$company, $weeklyReport])
                ->with('warning', '只有草稿狀態的週報可以發佈。');
        }

        $weeklyReport->update([
            'status' => WeeklyReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => $user->id,
        ]);

        return redirect()
            ->route('tenant.weekly-reports.edit', [$company, $weeklyReport])
            ->with('success', '週報已成功發佈。');
    }

    public function reopen(Request $request, Company $company, WeeklyReport $weeklyReport): RedirectResponse
    {
        $user = $request->user();
        $this->assertBelongsToCompany($user->company_id ?? null, $company);

        if (! $user->can('reopen', $weeklyReport)) {
            abort(403, '無權限重新開啟此週報');
        }

        if ($weeklyReport->status === WeeklyReport::STATUS_DRAFT) {
            return redirect()
                ->route('tenant.weekly-reports.edit', [$company, $weeklyReport])
                ->with('info', '此週報已是草稿狀態。');
        }

        $weeklyReport->update([
            'status' => WeeklyReport::STATUS_DRAFT,
            'submitted_at' => null,
            'submitted_by' => null,
        ]);

        AuditService::reopened($weeklyReport, '週報已重新開啟為草稿');

        return redirect()
            ->route('tenant.weekly-reports.edit', [$company, $weeklyReport])
            ->with('success', '週報已重新開啟，可繼續編輯。');
    }

    public function preview(Request $request, Company $company, WeeklyReport $weeklyReport): Response|RedirectResponse
    {
        $user = $request->user();
        $this->assertBelongsToCompany($user->company_id ?? null, $company);

        if ($weeklyReport->user_id !== $user->id) {
            abort(403, '無權限存取此週報');
        }

        if ($redirect = $this->ensureOnboarded($company, $user->role ?? null)) {
            return $redirect;
        }

        $weeklyReport->load(['items', 'user']);

        $weekDateRange = $this->getWeekDateRange(
            $weeklyReport->work_year,
            $weeklyReport->work_week,
            $company->timezone ?? config('app.timezone'),
        );

        // 計算下一週的日期範圍
        $nextWeekDate = CarbonImmutable::now($company->timezone ?? config('app.timezone'))
            ->setISODate($weeklyReport->work_year, $weeklyReport->work_week)
            ->addWeek();
        $nextWeekYear = (int) $nextWeekDate->isoFormat('GGGG');
        $nextWeekNumber = (int) $nextWeekDate->isoWeek();
        $nextWeekDateRange = $this->getWeekDateRange(
            $nextWeekYear,
            $nextWeekNumber,
            $company->timezone ?? config('app.timezone'),
        );

        return Inertia::render('weekly/preview', [
            'report' => [
                'id' => $weeklyReport->id,
                'workYear' => $weeklyReport->work_year,
                'workWeek' => $weeklyReport->work_week,
                'status' => $weeklyReport->status,
                'summary' => $weeklyReport->summary,
                'user' => [
                    'name' => $weeklyReport->user->name,
                    'email' => $weeklyReport->user->email,
                ],
                'currentWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item) => $this->transformCurrentItem($item)),
                'nextWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item) => $this->transformNextItem($item)),
            ],
            'company' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'name' => $company->name,
            ],
            'weekDateRange' => $weekDateRange,
            'nextWeekDateRange' => $nextWeekDateRange,
        ]);
    }

    private function syncItems(WeeklyReport $report, array $current, array $next): void
    {
        $report->items()->delete();

        $payload = [];

        foreach ($current as $index => $item) {
            $payload[] = [
                'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
                'sort_order' => $index,
                'title' => $item['title'],
                'content' => $item['content'],
                'hours_spent' => $item['hours_spent'],
                'planned_hours' => $item['planned_hours'] ?? null,
                'issue_reference' => $item['issue_reference'],
                'is_billable' => $item['is_billable'],
                'tags' => $item['tags'],
                'started_at' => ! empty($item['started_at']) ? $item['started_at'] : null,
                'ended_at' => ! empty($item['ended_at']) ? $item['ended_at'] : null,
            ];
        }

        foreach ($next as $index => $item) {
            $payload[] = [
                'type' => WeeklyReportItem::TYPE_NEXT_WEEK,
                'sort_order' => $index,
                'title' => $item['title'],
                'content' => $item['content'],
                'hours_spent' => 0,
                'planned_hours' => $item['planned_hours'],
                'issue_reference' => $item['issue_reference'],
                'is_billable' => false,
                'tags' => $item['tags'],
                'started_at' => ! empty($item['started_at']) ? $item['started_at'] : null,
                'ended_at' => ! empty($item['ended_at']) ? $item['ended_at'] : null,
            ];
        }

        if (! empty($payload)) {
            $report->items()->createMany($payload);
        }
    }

    private function prefillFromPreviousWeek(Company $company, int $userId, int $year, int $week): array
    {
        $target = CarbonImmutable::now($company->timezone ?? config('app.timezone'))
            ->setISODate($year, $week);

        $previous = WeeklyReport::query()
            ->with('items')
            ->where('company_id', $company->id)
            ->where('user_id', $userId)
            ->where(function ($query) use ($target) {
                $query->where('work_year', $target->subWeek()->isoFormat('GGGG'))
                    ->where('work_week', $target->subWeek()->isoWeek());
            })
            ->orderByDesc('work_year')
            ->orderByDesc('work_week')
            ->first();

        if (! $previous) {
            return [
                'currentWeek' => [],
                'nextWeek' => [],
            ];
        }

        $nextWeekPlans = $previous->items
            ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
            ->values()
            ->map(function (WeeklyReportItem $item): array {
                return [
                    'title' => $item->title,
                    'content' => $item->content,
                    'hours_spent' => $item->planned_hours ?? 0,
                    'planned_hours' => $item->planned_hours,
                    'issue_reference' => $item->issue_reference,
                    'is_billable' => false,
                    'tags' => $item->tags ?? [],
                ];
            });

        return [
            'currentWeek' => $nextWeekPlans
                ->map(function (array $plan): array {
                    return [
                        'title' => $plan['title'],
                        'content' => $plan['content'],
                        'hours_spent' => 0, // 實際工時初始為0，由用戶輸入
                        'planned_hours' => $plan['planned_hours'] ?? 0, // 保留預計工時
                        'issue_reference' => $plan['issue_reference'],
                        'is_billable' => $plan['is_billable'],
                        'tags' => $plan['tags'],
                    ];
                })
                ->values()
                ->all(),
            'nextWeek' => [],
        ];
    }

    private function transformCurrentItem(WeeklyReportItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'content' => $item->content,
            'hours_spent' => $item->hours_spent,
            'planned_hours' => $item->planned_hours,
            'issue_reference' => $item->issue_reference,
            'is_billable' => $item->is_billable,
            'tags' => $item->tags ?? [],
            'started_at' => $item->started_at?->format('Y-m-d'),
            'ended_at' => $item->ended_at?->format('Y-m-d'),
        ];
    }

    private function transformNextItem(WeeklyReportItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'content' => $item->content,
            'planned_hours' => $item->planned_hours,
            'issue_reference' => $item->issue_reference,
            'tags' => $item->tags ?? [],
            'started_at' => $item->started_at?->format('Y-m-d'),
            'ended_at' => $item->ended_at?->format('Y-m-d'),
        ];
    }

    private function assertBelongsToCompany(?int $userCompanyId, Company $company): void
    {
        if ($userCompanyId !== $company->id) {
            abort(403, '無權限存取此用戶資料');
        }
    }

    private function ensureOnboarded(Company $company, ?string $role): ?RedirectResponse
    {
        $managerRoles = ['owner', 'admin', 'company_admin'];

        if (in_array($role, $managerRoles, true) && $company->onboarded_at === null) {
            return redirect()->route('tenant.settings', $company)
                ->with('warning', '請先完成用戶設定，再編輯週報。');
        }

        return null;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveWeekFromRequest(Request $request, Company $company): array
    {
        $timezone = $company->timezone ?? config('app.timezone');
        $now = CarbonImmutable::now($timezone);

        $year = (int) $request->input('year', $now->isoFormat('GGGG'));
        $week = (int) $request->input('week', $now->isoWeek());

        $year = max(2000, min(2100, $year));
        $week = max(1, min(53, $week));

        return [$year, $week];
    }

    /**
     * 計算 ISO 週的開始日期（週一）和結束日期（週日）
     *
     * @return array{startDate: string, endDate: string}
     */
    private function getWeekDateRange(int $year, int $week, string $timezone): array
    {
        $date = CarbonImmutable::now($timezone)->setISODate($year, $week);
        $startDate = $date->startOfWeek()->format('Y-m-d');
        $endDate = $date->endOfWeek()->format('Y-m-d');

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * 計算從最早的週報到當前週之間所有缺失的週報
     *
     * @param  array<int, array{workYear: int, workWeek: int}>  $reports
     * @return array<int, array{year: int, week: int, startDate: string, endDate: string}>
     */
    private function calculateMissingWeeks(array $reports, int $currentYear, int $currentWeek, string $timezone): array
    {
        if (empty($reports)) {
            return [];
        }

        // 找到最早的週報
        $earliestReport = null;
        foreach ($reports as $report) {
            if ($earliestReport === null) {
                $earliestReport = $report;
            } else {
                $reportYear = $report['workYear'];
                $reportWeek = $report['workWeek'];
                $earliestYear = $earliestReport['workYear'];
                $earliestWeek = $earliestReport['workWeek'];

                if ($reportYear < $earliestYear || ($reportYear === $earliestYear && $reportWeek < $earliestWeek)) {
                    $earliestReport = $report;
                }
            }
        }

        if ($earliestReport === null) {
            return [];
        }

        // 建立已存在的週報集合（使用 year-week 作為鍵值）
        $existingWeeks = [];
        foreach ($reports as $report) {
            $key = "{$report['workYear']}-{$report['workWeek']}";
            $existingWeeks[$key] = true;
        }

        // 從最早的週報開始，逐週檢查到當前週
        $missingWeeks = [];
        $startYear = $earliestReport['workYear'];
        $startWeek = $earliestReport['workWeek'];
        $currentDate = CarbonImmutable::now($timezone)->setISODate($currentYear, $currentWeek);

        $checkDate = CarbonImmutable::now($timezone)->setISODate($startYear, $startWeek);
        while ($checkDate->lte($currentDate)) {
            $checkYear = (int) $checkDate->isoFormat('GGGG');
            $checkWeek = (int) $checkDate->isoWeek();
            $key = "{$checkYear}-{$checkWeek}";

            // 如果這個週報不存在，加入缺失列表
            if (! isset($existingWeeks[$key])) {
                $weekDateRange = $this->getWeekDateRange($checkYear, $checkWeek, $timezone);
                $missingWeeks[] = [
                    'year' => $checkYear,
                    'week' => $checkWeek,
                    'startDate' => $weekDateRange['startDate'],
                    'endDate' => $weekDateRange['endDate'],
                ];
            }

            // 移到下一週
            $checkDate = $checkDate->addWeek();
        }

        // 按照時間倒序排列（最新的在前）
        usort($missingWeeks, function (array $a, array $b) {
            if ($a['year'] !== $b['year']) {
                return $b['year'] - $a['year'];
            }

            return $b['week'] - $a['week'];
        });

        return $missingWeeks;
    }
}
