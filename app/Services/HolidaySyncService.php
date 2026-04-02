<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HolidaySyncService
{
    public const DATASET_NAME = '政府行政機關辦公日曆表';

    public const DATASET_URL = 'https://data.gov.tw/dataset/123662';

    private const API_BASE_URL = 'https://data.ntpc.gov.tw/api/datasets/308dcd75-6434-45bc-a95f-584da4fed251/json';

    private const PAGE_SIZE = 400;

    /**
     * 假日資料來源說明
     *
     * @return array{name: string, dataset_url: string, api_url: string, provider: string}
     */
    public static function sourceMetadata(): array
    {
        return [
            'name' => self::DATASET_NAME,
            'dataset_url' => self::DATASET_URL,
            'api_url' => self::API_BASE_URL,
            'provider' => '新北市政府人事處',
        ];
    }

    /**
     * 從新北市開放資料同步假期資料
     *
     * @return array{synced: int, errors: list<string>}
     */
    public function sync(?int $year = null): array
    {
        $errors = [];
        $holidays = collect();

        try {
            $page = 0;
            $hasMore = true;

            while ($hasMore) {
                $response = Http::timeout(30)->get(self::API_BASE_URL, [
                    'page' => $page,
                    'size' => self::PAGE_SIZE,
                ]);

                if (! $response->successful()) {
                    $errors[] = "API request failed on page {$page}: {$response->status()}";
                    break;
                }

                $payload = $response->json();

                if (! is_array($payload) || count($payload) === 0) {
                    $hasMore = false;

                    continue;
                }

                $parsed = collect($payload)
                    ->map(fn ($row) => is_array($row) ? $row : [])
                    ->filter(fn ($row) => ! empty($row['date']));
                $pageCount = $parsed->count();

                if ($year !== null) {
                    $parsed = $parsed->filter(fn ($row) => $row['year'] == $year);
                }

                $holidays = $holidays->merge($parsed);

                if ($pageCount < self::PAGE_SIZE) {
                    $hasMore = false;
                }

                $page++;
            }
        } catch (\Exception $e) {
            $errors[] = 'API sync failed: '.$e->getMessage();
            Log::error('HolidaySyncService error', ['error' => $e->getMessage()]);
        }

        if ($holidays->isNotEmpty()) {
            $transformed = $this->transform($holidays);
            $this->upsertHolidays($transformed);

            if ($year !== null) {
                $this->saveToLocalCache($year, $transformed);
            }

            return ['synced' => $transformed->count(), 'errors' => $errors];
        }

        return ['synced' => 0, 'errors' => $errors];
    }

    /**
     * 從本地快取或資料庫載入指定年份的假期
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function loadYear(int $year): Collection
    {
        $cached = $this->loadFromLocalCache($year);
        if ($cached !== null) {
            return $cached;
        }

        $holidays = Holiday::forYear($year);
        if ($holidays->isNotEmpty()) {
            return $holidays->map(fn ($h) => $h->toArray());
        }

        $result = $this->sync($year);
        if ($result['synced'] > 0) {
            return Holiday::forYear($year)->map(fn ($h) => $h->toArray());
        }

        return collect();
    }

    /**
     * 轉換 API 資料為模型格式
     *
     * @param  Collection<int, array<string, string>>  $data
     * @return Collection<int, array<string, mixed>>
     */
    private function transform(Collection $data): Collection
    {
        return $data->map(function ($row) {
            $date = Carbon::createFromFormat('Ymd', $row['date']);

            if (! $date) {
                return null;
            }

            $isHoliday = $row['isholiday'] === '是';
            $category = $this->mapCategory($row['holidaycategory'], $isHoliday);
            $isWorkdayOverride = $this->isWorkdayOverride($row['holidaycategory']);

            return [
                'holiday_date' => $date->format('Y-m-d'),
                'name' => $row['name'] ?: null,
                'is_holiday' => $isHoliday,
                'category' => $category,
                'note' => $row['description'] ?: ($row['holidaycategory'] ?: null),
                'source' => Holiday::SOURCE_NTPC,
                'is_workday_override' => $isWorkdayOverride,
                'iso_week' => (int) $date->isoWeek(),
                'iso_week_year' => (int) $date->isoWeekYear(),
            ];
        })->filter();
    }

    /**
     * 將 API 類別對應到模型類別
     */
    private function mapCategory(string $apiCategory, bool $isHoliday): string
    {
        if (str_contains($apiCategory, '補行上班')) {
            return Holiday::CATEGORY_MAKEUP_WORKDAY;
        }

        if (str_contains($apiCategory, '補假')) {
            return Holiday::CATEGORY_WEEKDAY_OFF;
        }

        if (str_contains($apiCategory, '國定假日') || str_contains($apiCategory, '放假')) {
            return Holiday::CATEGORY_NATIONAL;
        }

        if (str_contains($apiCategory, '星期')) {
            return $isHoliday ? Holiday::CATEGORY_WEEKEND : Holiday::CATEGORY_WEEKDAY_OFF;
        }

        return $isHoliday ? Holiday::CATEGORY_NATIONAL : Holiday::CATEGORY_WEEKDAY_OFF;
    }

    /**
     * 判斷是否為補行上班日
     */
    private function isWorkdayOverride(string $apiCategory): bool
    {
        return str_contains($apiCategory, '補行上班');
    }

    /**
     * 使用 upsert 寫入假期資料
     *
     * @param  Collection<int, array<string, mixed>>  $holidays
     */
    private function upsertHolidays(Collection $holidays): void
    {
        if ($holidays->isEmpty()) {
            return;
        }

        Holiday::upsert(
            $holidays->toArray(),
            ['holiday_date'],
            ['name', 'is_holiday', 'category', 'note', 'source', 'is_workday_override', 'iso_week', 'iso_week_year']
        );
    }

    /**
     * 儲存到本地快取
     *
     * @param  Collection<int, array<string, mixed>>  $holidays
     */
    private function saveToLocalCache(int $year, Collection $holidays): void
    {
        $path = "holidays/{$year}.json";

        Storage::put($path, json_encode($holidays->values()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 從本地快取載入
     *
     * @return Collection<int, array<string, mixed>>|null
     */
    private function loadFromLocalCache(int $year): ?Collection
    {
        $path = "holidays/{$year}.json";

        if (! Storage::exists($path)) {
            return null;
        }

        $content = Storage::get($path);
        $data = json_decode($content, true);

        if (! is_array($data) || empty($data)) {
            return null;
        }

        return collect($data);
    }
}
