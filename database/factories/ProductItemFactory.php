<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductItem>
 */
final class ProductItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ItemID' => $this->faker->unique()->randomNumber(6),
            'ProductID' => Product::factory()->create()->ProductID,
            'ProductName' => $this->faker->name(),
            'SKU' => $this->faker->unique()->randomNumber(6),
            'Quantity' => $this->faker->numberBetween(1, 100),
            'upSell' => $this->faker->boolean(),
            'extraProduct' => $this->faker->boolean(),
            'offerProducts' => $this->faker->boolean(),
            'active' => $this->faker->boolean(),
        ];
    }
}
