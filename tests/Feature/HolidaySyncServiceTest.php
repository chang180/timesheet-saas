<?php

use App\Models\Holiday;
use App\Services\HolidaySyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function makeHolidayPayload(string $date, string $category, ?string $name = null, ?string $description = null, string $isHoliday = '是'): array
{
    return [
        'date' => str_replace('-', '', $date),
        'year' => substr($date, 0, 4),
        'name' => $name,
        'isholiday' => $isHoliday,
        'holidaycategory' => $category,
        'description' => $description,
    ];
}

it('continues paging when syncing a specific year', function () {
    Storage::fake(config('filesystems.default', 'local'));

    $firstPage = collect(range(1, 400))
        ->map(fn (int $dayOffset) => makeHolidayPayload(
            date('Y-m-d', strtotime("2024-01-01 +{$dayOffset} days")),
            '星期六、星期日',
        ))
        ->all();

    Http::fakeSequence()
        ->push($firstPage)
        ->push([
            makeHolidayPayload('2026-04-03', '補假'),
        ]);

    $result = app(HolidaySyncService::class)->sync(2026);

    expect($result['synced'])->toBe(1);

    $holiday = Holiday::query()->whereDate('holiday_date', '2026-04-03')->first();

    expect($holiday)->not->toBeNull()
        ->and($holiday->category)->toBe(Holiday::CATEGORY_WEEKDAY_OFF)
        ->and($holiday->note)->toBe('補假')
        ->and($holiday->is_holiday)->toBeTrue()
        ->and($holiday->is_workday_override)->toBeFalse();
});

it('maps makeup workdays as workday overrides', function () {
    Storage::fake(config('filesystems.default', 'local'));

    Http::fakeSequence()->push([
        makeHolidayPayload('2026-02-07', '補行上班日', null, null, '否'),
    ]);

    $result = app(HolidaySyncService::class)->sync(2026);

    expect($result['synced'])->toBe(1);

    $holiday = Holiday::query()->whereDate('holiday_date', '2026-02-07')->firstOrFail();

    expect($holiday->category)->toBe(Holiday::CATEGORY_MAKEUP_WORKDAY)
        ->and($holiday->note)->toBe('補行上班日')
        ->and($holiday->is_holiday)->toBeFalse()
        ->and($holiday->is_workday_override)->toBeTrue();
});
