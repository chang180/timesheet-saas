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

        return Inertia::render('weekly/list', [
            'reports' => $reports,
            'company' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'name' => $company->name,
            ],
            'defaults' => [
                'year' => (int) $currentWeek->isoFormat('GGGG'),
                'week' => (int) $currentWeek->isoWeek(),
            ],
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
                'planned_hours' => null,
                'issue_reference' => $item['issue_reference'],
                'is_billable' => $item['is_billable'],
                'tags' => $item['tags'],
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
                        'hours_spent' => $plan['hours_spent'],
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
            'issue_reference' => $item->issue_reference,
            'is_billable' => $item->is_billable,
            'tags' => $item->tags ?? [],
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
}
