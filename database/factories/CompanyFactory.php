<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        return [
            'name' => fake()->company(),
            'slug' => Str::lower(Str::random(10)),
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
