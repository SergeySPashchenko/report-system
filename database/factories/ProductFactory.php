<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Gender;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ProductID' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'Product' => fake()->word(),
            'slug' => fake()->slug(),
            'newSystem' => fake()->boolean(),
            'Visible' => fake()->boolean(),
            'flyer' => fake()->boolean(),
            'main_category_id' => Category::factory(),
            'marketing_category_id' => Category::factory(),
            'gender_id' => Gender::factory(),
            'brand_id' => Brand::factory(),
        ];
    }
}
