<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gender>
 */
final class GenderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gender_id' => fake()->unique()->numberBetween(1, 999999),
            'gender_name' => fake()->word(),
            'slug' => fake()->slug(),
        ];
    }
}
