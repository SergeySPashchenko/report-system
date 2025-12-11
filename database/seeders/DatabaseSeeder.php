<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    // ❌ ВИДАЛЕНО: use WithoutModelEvents;
    // Це trait вимикає всі події моделі, включно з генерацією slug!

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ✅ Тепер slug згенерується автоматично
        User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'super-admin@example.com',
            'password' => Hash::make('password'),
            // username буде: "super-admin-user"
        ]);
        User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'super-admin2@example.com',
            'password' => Hash::make('password'),
            // username буде: "super-admin-user"
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            // username буде: "john-doe"
        ]);

        // Якщо потрібно створити багато користувачів
        User::factory(10)->create();
    }
}
