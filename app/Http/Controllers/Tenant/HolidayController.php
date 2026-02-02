<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\HolidayCacheService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct(
        private HolidayCacheService $holidayCacheService
    ) {}

    /**
     * 取得指定年份的假期列表
     */
    public function index(Request $request, Company $company): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);

        if ($year < 2020 || $year > 2030) {
            return response()->json([
                'error' => 'Invalid year. Must be between 2020 and 2030.',
            ], 422);
        }

        $holidays = $this->holidayCacheService->getHolidays($year);

        return response()->json([
            'data' => $holidays->map(fn ($h) => [
                'date' => $this->formatDate($h['holiday_date']),
                'name' => $h['name'],
                'is_holiday' => $h['is_holiday'],
                'category' => $h['category'],
                'note' => $h['note'],
                'is_workday_override' => $h['is_workday_override'],
            ])->values(),
            'year' => $year,
        ]);
    }

    /**
     * 取得指定 ISO 週的假期
     */
    public function week(Request $request, Company $company): JsonResponse
    {
        $isoYear = (int) $request->input('year', now()->isoWeekYear());
        $isoWeek = (int) $request->input('week', now()->isoWeek());

        if ($isoWeek < 1 || $isoWeek > 53) {
            return response()->json([
                'error' => 'Invalid ISO week. Must be between 1 and 53.',
            ], 422);
        }

        $holidays = $this->holidayCacheService->getHolidaysForWeek($isoYear, $isoWeek);

        return response()->json([
            'data' => $holidays->map(fn ($h) => [
                'date' => $this->formatDate($h['holiday_date']),
                'name' => $h['name'],
                'is_holiday' => $h['is_holiday'],
                'category' => $h['category'],
                'note' => $h['note'],
                'is_workday_override' => $h['is_workday_override'],
            ])->values(),
            'year' => $isoYear,
            'week' => $isoWeek,
        ]);
    }

    /**
     * 格式化日期為 Y-m-d 字串
     */
    private function formatDate(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            return Carbon::parse($date)->format('Y-m-d');
        }

        return (string) $date;
    }
}
