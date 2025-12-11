<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Queries\UserQuery;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    private UserQuery $userQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQuery = new UserQuery();
        $this->userService = new UserService($this->userQuery);
    }

    public function test_can_get_paginated_users(): void
    {
        User::factory()->count(20)->create();

        $result = $this->userService->getPaginatedUsers();

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBeGreaterThan(0)
            ->and($result->perPage())->toBe(15);
    }

    public function test_can_search_users(): void
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $result = $this->userService->getPaginatedUsers(search: 'John');

        expect($result->count())->toBe(1)
            ->and($result->first()->name)->toBe('John Doe');
    }

    public function test_can_sort_users(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Charlie']);

        $result = $this->userService->getPaginatedUsers(sortBy: 'name', sortDirection: 'asc');

        $names = $result->pluck('name')->toArray();
        expect($names)->toContain('Alice', 'Bob', 'Charlie');
    }

    public function test_can_find_user_by_username(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $found = $this->userService->findByUsername('testuser');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($user->id)
            ->and($found->username)->toBe('testuser');
    }

    public function test_returns_null_when_username_not_found(): void
    {
        $found = $this->userService->findByUsername('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_can_find_user_by_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $found = $this->userService->findByEmail('test@example.com');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($user->id)
            ->and($found->email)->toBe('test@example.com');
    }

    public function test_returns_null_when_email_not_found(): void
    {
        $found = $this->userService->findByEmail('nonexistent@example.com');

        expect($found)->toBeNull();
    }

    public function test_can_create_user(): void
    {
        $data = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ];

        $user = $this->userService->create($data);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('New User')
            ->and($user->email)->toBe('newuser@example.com')
            ->and(Hash::check('password123', $user->password))->toBeTrue();

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $updated = $this->userService->update($user, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_update_user_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldpassword')]);

        $updated = $this->userService->update($user, ['password' => 'newpassword']);

        expect(Hash::check('newpassword', $updated->password))->toBeTrue()
            ->and(Hash::check('oldpassword', $updated->password))->toBeFalse();
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $result = $this->userService->delete($user);

        expect($result)->toBeTrue();

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_can_restore_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $restored = $this->userService->restore($user->id);

        expect($restored->id)->toBe($user->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_user(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $result = $this->userService->forceDelete($userId);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
        ]);
    }

    public function test_can_get_active_users_count(): void
    {
        User::factory()->count(3)->create(['email_verified_at' => now()]);
        User::factory()->count(2)->create(['email_verified_at' => null]);

        $count = $this->userService->getActiveUsersCount();

        expect($count)->toBe(3);
    }

    public function test_can_get_recent_users(): void
    {
        User::factory()->count(15)->create();

        $recent = $this->userService->getRecentUsers(limit: 10);

        expect($recent)->toBeArray()
            ->and(count($recent))->toBeLessThanOrEqual(10);
    }

    public function test_can_check_if_user_is_active(): void
    {
        $activeUser = User::factory()->create(['email_verified_at' => now()]);
        $inactiveUser = User::factory()->create(['email_verified_at' => null]);
        $deletedUser = User::factory()->create(['email_verified_at' => now()]);
        $deletedUser->delete();

        expect($this->userService->isActive($activeUser))->toBeTrue()
            ->and($this->userService->isActive($inactiveUser))->toBeFalse()
            ->and($this->userService->isActive($deletedUser))->toBeFalse();
    }

    public function test_can_activate_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $activated = $this->userService->activate($user);

        expect($activated->email_verified_at)->not->toBeNull();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified_at' => $activated->email_verified_at,
        ]);
    }

    public function test_activate_does_not_change_already_verified_user(): void
    {
        $originalDate = now()->subDays(5);
        $user = User::factory()->create(['email_verified_at' => $originalDate]);

        $activated = $this->userService->activate($user);

        expect($activated->email_verified_at->format('Y-m-d'))->toBe($originalDate->format('Y-m-d'));
    }

    public function test_can_deactivate_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('test-token');

        $deactivated = $this->userService->deactivate($user);

        expect($deactivated->email_verified_at)->toBeNull();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_can_get_statistics(): void
    {
        User::factory()->count(5)->create(['email_verified_at' => now()]);
        User::factory()->count(2)->create(['email_verified_at' => null]);
        $deleted = User::factory()->create();
        $deleted->delete();

        $stats = $this->userService->getStatistics();

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['total', 'active', 'inactive', 'deleted', 'registered_today', 'registered_this_week', 'registered_this_month'])
            ->and($stats['total'])->toBeGreaterThan(0)
            ->and($stats['active'])->toBe(5)
            ->and($stats['inactive'])->toBe(2)
            ->and($stats['deleted'])->toBe(1);
    }
}
