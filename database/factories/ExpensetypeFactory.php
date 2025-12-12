<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expensetype;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expensetype>
 */
final class ExpensetypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ExpenseTypeID' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'Name' => fake()->word(),
            //
        ];
    }
}
