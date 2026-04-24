<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalWeeklyReportUpsertRequest;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonalWeeklyReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $filterYear = $request->input('filter_year');
        $filterStatus = $request->input('filter_status');

        $query = $this->baseQuery($user->id)->with('items');

        if ($filterYear && $filterYear !== 'all') {
            $query->where('work_year', (int) $filterYear);
        }

        if ($filterStatus && $filterStatus !== 'all') {
            $query->where('status', $filterStatus);
        }

        $reports = $query
            ->orderByDesc('work_year')
            ->orderByDesc('work_week')
            ->get()
            ->map(fn (WeeklyReport $report): array => [
                'id' => $report->id,
                'workYear' => $report->work_year,
                'workWeek' => $report->work_week,
                'status' => $report->status,
                'summary' => $report->summary,
                'totalHours' => (float) $report->items
                    ->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)
                    ->sum('hours_spent'),
                'updatedAt' => $report->updated_at?->toIso8601String(),
            ]);

        $timezone = config('app.timezone');
        $currentWeek = CarbonImmutable::now($timezone)->startOfWeek();
        $currentYear = (int) $currentWeek->isoFormat('GGGG');
        $currentWeekNumber = (int) $currentWeek->isoWeek();

        $availableYears = $this->baseQuery($user->id)
            ->selectRaw('DISTINCT work_year')
            ->orderByDesc('work_year')
            ->pluck('work_year')
            ->values()
            ->all();

        if (! in_array($currentYear, $availableYears, true)) {
            array_unshift($availableYears, $currentYear);
        }

        return Inertia::render('personal/weekly-reports/list', [
            'reports' => $reports,
            'defaults' => [
                'year' => $currentYear,
                'week' => $currentWeekNumber,
            ],
            'weekDateRange' => $this->getWeekDateRange($currentYear, $currentWeekNumber, $timezone),
            'filters' => [
                'year' => $filterYear ?? 'all',
                'status' => $filterStatus ?? 'all',
            ],
            'availableYears' => $availableYears,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        [$year, $week] = $this->resolveWeekFromRequest($request);

        $existing = $this->baseQuery($user->id)
            ->where('work_year', $year)
            ->where('work_week', $week)
            ->first();

        if ($existing) {
            return redirect()->route('personal.weekly-reports.edit', $existing)
                ->with('info', '當週週報已存在，為你開啟編輯畫面。');
        }

        $timezone = config('app.timezone');
        [$nextYear, $nextWeek] = $this->addWeek($year, $week, $timezone);

        return Inertia::render('personal/weekly-reports/form', [
            'mode' => 'create',
            'report' => null,
            'defaults' => [
                'year' => $year,
                'week' => $week,
            ],
            'weekDateRange' => $this->getWeekDateRange($year, $week, $timezone),
            'nextWeekDateRange' => $this->getWeekDateRange($nextYear, $nextWeek, $timezone),
        ]);
    }

    public function store(PersonalWeeklyReportUpsertRequest $request): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->payload();

        $existing = $this->baseQuery($user->id)
            ->where('work_year', $payload['work_year'])
            ->where('work_week', $payload['work_week'])
            ->first();

        if ($existing) {
            return redirect()->route('personal.weekly-reports.edit', $existing)
                ->with('info', '當週週報已存在，為你開啟編輯畫面。');
        }

        /** @var WeeklyReport $report */
        $report = WeeklyReport::create([
            'company_id' => null,
            'user_id' => $user->id,
            'division_id' => null,
            'department_id' => null,
            'team_id' => null,
            'work_year' => $payload['work_year'],
            'work_week' => $payload['work_week'],
            'summary' => $payload['summary'],
            'metadata' => $payload['metadata'],
            'status' => WeeklyReport::STATUS_DRAFT,
        ]);

        $this->syncItems($report, $payload['current_week'], $payload['next_week']);

        return redirect()->route('personal.weekly-reports.edit', $report)
            ->with('success', '已建立週報草稿，可繼續編輯。');
    }

    public function show(Request $request, WeeklyReport $weeklyReport): Response
    {
        $this->authorizePersonalReport($request, $weeklyReport);

        $weeklyReport->load('items');

        return Inertia::render('personal/weekly-reports/show', [
            'report' => [
                'id' => $weeklyReport->id,
                'workYear' => $weeklyReport->work_year,
                'workWeek' => $weeklyReport->work_week,
                'status' => $weeklyReport->status,
                'isPublic' => $weeklyReport->is_public,
                'publishedAt' => $weeklyReport->published_at?->toIso8601String(),
                'summary' => $weeklyReport->summary,
                'currentWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => [
                        'title' => $item->title,
                        'content' => $item->content,
                        'hoursSpent' => $item->hours_spent,
                        'plannedHours' => $item->planned_hours,
                        'tags' => $item->tags ?? [],
                    ]),
                'nextWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => [
                        'title' => $item->title,
                        'content' => $item->content,
                        'plannedHours' => $item->planned_hours,
                        'tags' => $item->tags ?? [],
                    ]),
            ],
            'user' => [
                'name' => $request->user()->name,
                'handle' => $request->user()->handle,
            ],
        ]);
    }

    public function edit(Request $request, WeeklyReport $weeklyReport): Response
    {
        $this->authorizePersonalReport($request, $weeklyReport);

        $weeklyReport->load('items');

        $timezone = config('app.timezone');

        return Inertia::render('personal/weekly-reports/form', [
            'mode' => 'edit',
            'report' => [
                'id' => $weeklyReport->id,
                'workYear' => $weeklyReport->work_year,
                'workWeek' => $weeklyReport->work_week,
                'status' => $weeklyReport->status,
                'isPublic' => $weeklyReport->is_public,
                'publishedAt' => $weeklyReport->published_at?->toIso8601String(),
                'summary' => $weeklyReport->summary,
                'currentWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => $this->transformCurrentItem($item)),
                'nextWeek' => $weeklyReport->items
                    ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => $this->transformNextItem($item)),
            ],
            'defaults' => [
                'year' => $weeklyReport->work_year,
                'week' => $weeklyReport->work_week,
            ],
            'weekDateRange' => $this->getWeekDateRange(
                $weeklyReport->work_year,
                $weeklyReport->work_week,
                $timezone,
            ),
            'nextWeekDateRange' => (function () use ($weeklyReport, $timezone): array {
                [$ny, $nw] = $this->addWeek($weeklyReport->work_year, $weeklyReport->work_week, $timezone);

                return $this->getWeekDateRange($ny, $nw, $timezone);
            })(),
        ]);
    }

    public function update(
        PersonalWeeklyReportUpsertRequest $request,
        WeeklyReport $weeklyReport,
    ): RedirectResponse {
        $this->authorizePersonalReport($request, $weeklyReport);

        $payload = $request->payload();

        $weeklyReport->forceFill([
            'work_year' => $payload['work_year'],
            'work_week' => $payload['work_week'],
            'summary' => $payload['summary'],
            'metadata' => $payload['metadata'],
        ])->save();

        $this->syncItems($weeklyReport, $payload['current_week'], $payload['next_week']);

        return redirect()->route('personal.weekly-reports.edit', $weeklyReport)
            ->with('success', '週報已更新。');
    }

    public function submit(Request $request, WeeklyReport $weeklyReport): RedirectResponse
    {
        $this->authorizePersonalReport($request, $weeklyReport);

        if ($weeklyReport->status !== WeeklyReport::STATUS_DRAFT) {
            return redirect()->route('personal.weekly-reports.edit', $weeklyReport)
                ->with('warning', '只有草稿狀態的週報可以發佈。');
        }

        $weeklyReport->update([
            'status' => WeeklyReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => $request->user()->id,
        ]);

        return redirect()->route('personal.weekly-reports.edit', $weeklyReport)
            ->with('success', '週報已成功發佈。');
    }

    public function togglePublic(Request $request, WeeklyReport $weeklyReport): RedirectResponse
    {
        $this->authorizePersonalReport($request, $weeklyReport);

        $isPublic = (bool) $request->boolean('is_public');

        if ($isPublic && $weeklyReport->status !== WeeklyReport::STATUS_SUBMITTED) {
            return back()->withErrors([
                'is_public' => '僅已提交（submitted）的週報可公開分享。',
            ]);
        }

        if ($isPublic && $request->user()->handle === null) {
            return back()->withErrors([
                'is_public' => '請先到「設定 → 我的代號」建立你的代號。',
            ]);
        }

        $weeklyReport->forceFill([
            'is_public' => $isPublic,
            'published_at' => $isPublic
                ? ($weeklyReport->published_at ?? now())
                : $weeklyReport->published_at,
        ])->save();

        return redirect()->route('personal.weekly-reports.edit', $weeklyReport)
            ->with('success', $isPublic ? '已公開此週報。' : '已關閉公開分享。');
    }

    public function destroy(Request $request, WeeklyReport $weeklyReport): RedirectResponse
    {
        $this->authorizePersonalReport($request, $weeklyReport);

        $weeklyReport->items()->delete();
        $weeklyReport->delete();

        return redirect()->route('personal.weekly-reports')
            ->with('success', '週報已刪除。');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<WeeklyReport>
     */
    private function baseQuery(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return WeeklyReport::query()
            ->whereNull('company_id')
            ->where('user_id', $userId);
    }

    private function authorizePersonalReport(Request $request, WeeklyReport $weeklyReport): void
    {
        $user = $request->user();

        if ($weeklyReport->company_id !== null || $weeklyReport->user_id !== $user->id) {
            abort(403, '無權限存取此週報');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $current
     * @param  array<int, array<string, mixed>>  $next
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array{0:int,1:int}
     */
    private function resolveWeekFromRequest(Request $request): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        $year = (int) $request->input('year', $now->isoFormat('GGGG'));
        $week = (int) $request->input('week', $now->isoWeek());

        $year = max(2000, min(2100, $year));
        $week = max(1, min(53, $week));

        return [$year, $week];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function addWeek(int $year, int $week, string $timezone): array
    {
        $next = CarbonImmutable::now($timezone)
            ->setISODate($year, $week)
            ->addWeek();

        return [(int) $next->isoFormat('GGGG'), (int) $next->isoWeek()];
    }

    /**
     * @return array{startDate:string,endDate:string}
     */
    private function getWeekDateRange(int $year, int $week, string $timezone): array
    {
        $date = CarbonImmutable::now($timezone)->setISODate($year, $week);

        return [
            'startDate' => $date->startOfWeek()->format('Y-m-d'),
            'endDate' => $date->endOfWeek()->format('Y-m-d'),
        ];
    }
}
