<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_verified_active_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
    }

    public function test_blocks_unverified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your email address is not verified.',
                'error' => 'email_not_verified',
            ]);
    }

    public function test_blocks_deactivated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->delete();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your account has been deactivated.',
                'error' => 'account_deactivated',
            ]);
    }

    public function test_blocks_unauthenticated_user(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_allows_access_to_public_auth_routes(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201);
    }
}
