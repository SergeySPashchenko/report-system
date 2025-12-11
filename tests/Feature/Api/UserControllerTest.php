<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_list_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'username', 'email', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_users(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/users?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    public function test_can_show_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/users/{$user->username}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name);
    }

    public function test_can_create_user(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('data.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_can_update_own_profile(): void
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/users/{$this->user->username}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_cannot_update_other_user_profile(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/users/{$otherUser->username}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_user(): void
    {
        $userToDelete = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/users/{$userToDelete->username}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', [
            'id' => $userToDelete->id,
        ]);
    }

    public function test_validation_fails_for_invalid_email(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_validation_fails_for_duplicate_email(): void
    {
        $existingUser = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Test User',
                'email' => $existingUser->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_guest_cannot_access_users_endpoint(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_can_get_user_statistics(): void
    {
        User::factory()->count(5)->create();
        User::factory()->count(2)->create(['email_verified_at' => null]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/users/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'active',
                'inactive',
                'deleted',
                'registered_today',
                'registered_this_week',
                'registered_this_month',
            ])
            ->assertJsonPath('total', 8); // 5 + 2 + 1 (current user)
    }

    public function test_can_restore_soft_deleted_user(): void
    {
        $deletedUser = User::factory()->create();
        $deletedUser->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/users/{$deletedUser->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $deletedUser->id);

        $this->assertDatabaseHas('users', [
            'id' => $deletedUser->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_user(): void
    {
        $userToDelete = User::factory()->create();
        $userId = $userToDelete->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/users/{$userId}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
        ]);
    }

    public function test_can_sort_users(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Charlie']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/users?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $users = $response->json('data');
        $names = array_column($users, 'name');
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], array_slice($names, 0, 3));
    }

    public function test_can_paginate_users(): void
    {
        User::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/users?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $perPage = is_array($meta['per_page']) ? $meta['per_page'][0] : $meta['per_page'];
        expect($perPage)->toBe(10);

        $data = $response->json('data');
        expect($data)->toBeArray()
            ->and(count($data))->toBeLessThanOrEqual(10);
    }
}
