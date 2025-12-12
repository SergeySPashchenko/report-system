<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Access;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Access>
 */
final class AccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'accessible_id' => Company::factory(),
            'accessible_type' => Company::class,
        ];
    }
}
