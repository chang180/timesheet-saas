<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HolidayCacheService
{
    private const CACHE_TTL = 60 * 60 * 24;

    public function __construct(
        private HolidaySyncService $syncService
    ) {}

    /**
     * 確保指定年份的假期資料已載入（從快取、資料庫或遠端 API）
     */
    public function ensureYearLoaded(int $year): void
    {
        $cacheKey = $this->getCacheKey($year);

        if (Cache::has($cacheKey)) {
            return;
        }

        $holidays = Holiday::forYear($year);

        if ($holidays->isEmpty() || ! $this->hasRequiredNationalHolidays($holidays, $year)) {
            $this->syncService->sync($year);
            $holidays = Holiday::forYear($year);
        }

        if ($holidays->isNotEmpty()) {
            Cache::put($cacheKey, $holidays->toArray(), self::CACHE_TTL);
        }
    }

    /**
     * 取得指定年份的假期（優先從快取取得）
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getHolidays(int $year): Collection
    {
        $cacheKey = $this->getCacheKey($year);

        return collect(Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {
            $holidays = Holiday::forYear($year);

            if ($holidays->isEmpty()) {
                $this->syncService->sync($year);
                $holidays = Holiday::forYear($year);
            }

            return $holidays->toArray();
        }));
    }

    /**
     * 取得指定 ISO 週的假期
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getHolidaysForWeek(int $isoYear, int $isoWeek): Collection
    {
        $this->ensureYearLoaded($isoYear);

        return Holiday::forIsoWeek($isoYear, $isoWeek)
            ->map(fn ($h) => $h->toArray());
    }

    /**
     * 清除指定年份的快取
     */
    public function clearCache(int $year): void
    {
        Cache::forget($this->getCacheKey($year));
    }

    /**
     * 重新同步並更新快取
     */
    public function refreshYear(int $year): void
    {
        $this->clearCache($year);
        $this->syncService->sync($year);
        $this->ensureYearLoaded($year);
    }

    /**
     * 取得快取鍵名
     */
    private function getCacheKey(int $year): string
    {
        return "holidays:{$year}";
    }

    /**
     * 檢查是否包含必要的國定假日
     *
     * @param  Collection<int, Holiday>  $holidays
     */
    private function hasRequiredNationalHolidays(Collection $holidays, int $year): bool
    {
        $requiredDates = [
            "{$year}-01-01",
            "{$year}-02-28",
            "{$year}-10-10",
        ];

        $holidayDates = $holidays->pluck('holiday_date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        foreach ($requiredDates as $date) {
            if (! in_array($date, $holidayDates)) {
                return false;
            }
        }

        return true;
    }
}
