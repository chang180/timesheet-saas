<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\WeeklyReportItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WeeklyReport>
 */
class WeeklyReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $date = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'department_id' => $user->department_id,
            'team_id' => $user->team_id,
            'work_year' => (int) $date->format('o'),
            'work_week' => (int) $date->format('W'),
            'status' => 'draft',
            'summary' => fake()->sentence(),
            'metadata' => [],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($report) {
            $itemsCount = fake()->numberBetween(2, 4);
            for ($i = 0; $i < $itemsCount; $i++) {
                WeeklyReportItem::factory()
                    ->for($report)
                    ->state([
                        'sort_order' => $i,
                        'type' => WeeklyReportItem::TYPE_CURRENT_WEEK,
                    ])
                    ->create();
            }

            $planCount = fake()->numberBetween(1, 3);
            for ($i = 0; $i < $planCount; $i++) {
                WeeklyReportItem::factory()
                    ->nextWeek()
                    ->for($report)
                    ->state(['sort_order' => $i])
                    ->create();
            }
        });
    }

    /**
     * Attach report to existing company/user.
     *
     * @param  Company  $company
     * @param  User|null  $user
     */
    public function forCompany(Company $company, ?User $user = null): self
    {
        return $this->state(function () use ($company, $user): array {
            $user ??= User::factory()->create([
                'company_id' => $company->id,
            ]);

            return [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'division_id' => $user->division_id,
                'department_id' => $user->department_id,
                'team_id' => $user->team_id,
            ];
        });
    }

    public function submitted(): self
    {
        return $this->state([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }
}

