<?php

namespace Database\Factories;

use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WeeklyReportItem>
 */
class WeeklyReportItemFactory extends Factory
{
    protected $model = WeeklyReportItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'weekly_report_id' => WeeklyReport::factory(),
            'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
            'sort_order' => 0,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'hours_spent' => fake()->randomFloat(1, 0.5, 10),
            'planned_hours' => null,
            'issue_reference' => fake()->boolean(40) ? 'JIRA-'.fake()->numberBetween(1000, 9999) : null,
            'is_billable' => fake()->boolean(),
            'tags' => fake()->boolean(30) ? ['urgent'] : [],
            'started_at' => null,
            'ended_at' => null,
            'metadata' => [],
        ];
    }

    public function nextWeek(): self
    {
        return $this->state([
            'type' => WeeklyReportItem::TYPE_NEXT_WEEK,
            'hours_spent' => 0,
            'planned_hours' => fake()->randomFloat(1, 1, 8),
        ]);
    }

    public function forReport(WeeklyReport $report): self
    {
        return $this->state(fn (): array => [
            'weekly_report_id' => $report->id,
        ]);
    }
}

