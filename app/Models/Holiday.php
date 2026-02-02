<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    public const SOURCE_NTPC = 'ntpc';

    public const CATEGORY_NATIONAL = 'national';

    public const CATEGORY_WEEKDAY_OFF = 'weekday_off';

    public const CATEGORY_MAKEUP_WORKDAY = 'makeup_workday';

    public const CATEGORY_WEEKEND = 'weekend';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'holiday_date',
        'name',
        'is_holiday',
        'category',
        'note',
        'source',
        'is_workday_override',
        'iso_week',
        'iso_week_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_holiday' => 'boolean',
            'is_workday_override' => 'boolean',
            'iso_week' => 'integer',
            'iso_week_year' => 'integer',
        ];
    }

    /**
     * 取得指定年份的所有假期
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function forYear(int $year): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->whereYear('holiday_date', $year)
            ->orderBy('holiday_date')
            ->get();
    }

    /**
     * 取得指定 ISO 週的所有假期
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function forIsoWeek(int $isoYear, int $isoWeek): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->where('iso_week_year', $isoYear)
            ->where('iso_week', $isoWeek)
            ->orderBy('holiday_date')
            ->get();
    }
}
