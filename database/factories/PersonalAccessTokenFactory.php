<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * @extends Factory<PersonalAccessToken>
 */
final class PersonalAccessTokenFactory extends Factory
{
    protected $model = PersonalAccessToken::class;

    public function definition(): array
    {
        return [
            'tokenable_id' => $this->faker->randomNumber(),
            'tokenable_type' => $this->faker->word(),
            'name' => $this->faker->name(),
            'token' => Str::random(10),
            'abilities' => $this->faker->word(),
            'last_used_at' => Date::now(),
            'expires_at' => Date::now(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
