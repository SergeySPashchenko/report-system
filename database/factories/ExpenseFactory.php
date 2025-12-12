<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
final class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'ExpenseDate' => fake()->dateTimeBetween('-1 year', 'now'),
            'Expense' => fake()->randomFloat(2, 0, 1000000),
            'ProductID' => Product::factory()->create()->ProductID,
            'ExpenseID' => Expensetype::factory()->create()->ExpenseTypeID,
        ];
    }
}
