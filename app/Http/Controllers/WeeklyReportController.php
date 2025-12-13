<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeeklyReportUpsertRequest;
use App\Models\Company;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
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

        $reports = WeeklyReport::query()
            ->with(['items'])
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
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
}
