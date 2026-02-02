<?php

namespace Database\Factories;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::instance($this->faker->dateTimeBetween('-1 year', '+1 year'));

        return [
            'holiday_date' => $date->format('Y-m-d'),
            'name' => $this->faker->randomElement(['元旦', '春節', '清明節', '端午節', '中秋節', '國慶日']),
            'is_holiday' => true,
            'category' => Holiday::CATEGORY_NATIONAL,
            'note' => null,
            'source' => Holiday::SOURCE_NTPC,
            'is_workday_override' => false,
            'iso_week' => $date->isoWeek(),
            'iso_week_year' => $date->isoWeekYear(),
        ];
    }

    /**
     * 國定假日狀態
     */
    public function national(): static
    {
        return $this->state(fn () => [
            'is_holiday' => true,
            'category' => Holiday::CATEGORY_NATIONAL,
        ]);
    }

    /**
     * 補行上班日狀態
     */
    public function makeupWorkday(): static
    {
        return $this->state(fn () => [
            'is_holiday' => false,
            'category' => Holiday::CATEGORY_MAKEUP_WORKDAY,
            'is_workday_override' => true,
            'name' => '補行上班日',
        ]);
    }

    /**
     * 週末狀態
     */
    public function weekend(): static
    {
        return $this->state(fn () => [
            'is_holiday' => true,
            'category' => Holiday::CATEGORY_WEEKEND,
            'name' => null,
        ]);
    }

    /**
     * 指定日期
     */
    public function forDate(Carbon|string $date): static
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $this->state(fn () => [
            'holiday_date' => $carbon->format('Y-m-d'),
            'iso_week' => $carbon->isoWeek(),
            'iso_week_year' => $carbon->isoWeekYear(),
        ]);
    }
}
