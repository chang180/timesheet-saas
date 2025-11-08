<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'status' => 'active',
            'user_limit' => 50,
            'current_user_count' => 0,
            'timezone' => 'Asia/Taipei',
            'branding' => [
                'color' => fake()->hexColor(),
                'logo' => fake()->imageUrl(),
            ],
            'onboarded_at' => now(),
        ];
    }
}
