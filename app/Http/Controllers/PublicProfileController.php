<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Support\ReservedHandles;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
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
                    ]),
                'nextWeek' => $report->items
                    ->where('type', WeeklyReportItem::TYPE_NEXT_WEEK)
                    ->values()
                    ->map(fn (WeeklyReportItem $item): array => [
                        'title' => $item->title,
                        'content' => $item->content,
                        'plannedHours' => $item->planned_hours !== null ? (float) $item->planned_hours : null,
                        'tags' => $item->tags ?? [],
                    ]),
            ],
        ]);
    }
}
