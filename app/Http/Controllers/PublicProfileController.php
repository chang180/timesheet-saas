<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Services\HolidayCacheService;
use App\Services\HolidaySyncService;
use App\Support\ReservedHandles;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function __construct(
        private HolidayCacheService $holidayCacheService
    ) {}

    public function show(string $handle): Response
    {
        abort_if(ReservedHandles::isReserved($handle), 404);

        $user = User::findByHandle($handle);
        abort_if($user === null, 404);

        $reports = WeeklyReport::query()
            ->where('user_id', $user->id)
            ->whereNull('company_id')
            ->where('status', WeeklyReport::STATUS_SUBMITTED)
            ->where('is_public', true)
            ->orderByDesc('work_year')
            ->orderByDesc('work_week')
            ->paginate(20)
            ->through(fn (WeeklyReport $report): array => [
                'workYear' => $report->work_year,
                'workWeek' => $report->work_week,
                'summary' => $report->summary,
                'publishedAt' => $report->published_at?->toIso8601String(),
            ]);

        return Inertia::render('public/profile', [
            'profile' => [
                'handle' => $user->handle,
                'name' => $user->name,
            ],
            'reports' => $reports,
        ]);
    }

    public function showReport(string $handle, int $year, int $week): Response
    {
        abort_if(ReservedHandles::isReserved($handle), 404);

        $user = User::findByHandle($handle);
        abort_if($user === null, 404);

        /** @var WeeklyReport|null $report */
        $report = WeeklyReport::query()
            ->where('user_id', $user->id)
            ->whereNull('company_id')
            ->where('work_year', $year)
            ->where('work_week', $week)
            ->where('status', WeeklyReport::STATUS_SUBMITTED)
            ->where('is_public', true)
            ->with('items')
            ->first();

        abort_if($report === null, 404);

        $timezone = config('app.timezone');
        $weekDateRange = $this->getWeekDateRange($report->work_year, $report->work_week, $timezone);
        $holidayCalendar = $this->buildHolidayCalendarProps($report->work_year, $report->work_week, $timezone);

        return Inertia::render('public/report', [
            'profile' => [
                'handle' => $user->handle,
                'name' => $user->name,
            ],
            'report' => [
                'workYear' => $report->work_year,
                'workWeek' => $report->work_week,
                'summary' => $report->summary,
                'publishedAt' => $report->published_at?->toIso8601String(),
                'currentWeek' => $report->items
                    ->where('type', WeeklyReportItem::TYPE_CURRENT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => [
                        'title' => $item->title,
                        'content' => $item->content,
                        'hoursSpent' => (float) $item->hours_spent,
                        'tags' => $item->tags ?? [],
                        'startedAt' => $item->started_at?->toDateString(),
                        'endedAt' => $item->ended_at?->toDateString(),
                    ]),
                'nextWeek' => $report->items
                    ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => [
                        'title' => $item->title,
                        'content' => $item->content,
                        'plannedHours' => $item->planned_hours !== null ? (float) $item->planned_hours : null,
                        'tags' => $item->tags ?? [],
                        'startedAt' => $item->started_at?->toDateString(),
                        'endedAt' => $item->ended_at?->toDateString(),
                    ]),
            ],
            'weekDateRange' => $weekDateRange,
            'holidayCalendar' => $holidayCalendar,
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function buildHolidayCalendarProps(int $year, int $week, string $timezone): array
    {
        $currentWeekDate = CarbonImmutable::now($timezone)->setISODate($year, $week);
        $currentWeekYear = (int) $currentWeekDate->isoFormat('GGGG');
        $currentWeekNumber = (int) $currentWeekDate->isoWeek();
        $nextWeekDate = $currentWeekDate->addWeek();
        $nextWeekYear = (int) $nextWeekDate->isoFormat('GGGG');
        $nextWeekNumber = (int) $nextWeekDate->isoWeek();

        return [
            'currentWeek' => [
                'year' => $currentWeekYear,
                'week' => $currentWeekNumber,
                'holidays' => $this->formatHolidayProps(
                    $this->holidayCacheService->getHolidaysForWeek($currentWeekYear, $currentWeekNumber)->all()
                ),
            ],
            'nextWeek' => [
                'year' => $nextWeekYear,
                'week' => $nextWeekNumber,
                'holidays' => $this->formatHolidayProps(
                    $this->holidayCacheService->getHolidaysForWeek($nextWeekYear, $nextWeekNumber)->all()
                ),
            ],
            'source' => HolidaySyncService::sourceMetadata(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $holidays
     * @return array<int, array<string, mixed>>
     */
    private function formatHolidayProps(array $holidays): array
    {
        return array_values(collect($holidays)
            ->map(fn (array $h): array => [
                'date' => CarbonImmutable::parse($h['holiday_date'])->format('Y-m-d'),
                'name' => $h['name'] ?? null,
                'is_holiday' => (bool) ($h['is_holiday'] ?? false),
                'category' => $h['category'] ?? 'national',
                'note' => $h['note'] ?? null,
                'is_workday_override' => (bool) ($h['is_workday_override'] ?? false),
            ])
            ->values()
            ->all());
    }
}
