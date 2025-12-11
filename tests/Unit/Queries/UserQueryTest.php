<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Models\User;
use App\Queries\UserQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserQueryTest extends TestCase
{
    use RefreshDatabase;

    private UserQuery $userQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userQuery = new UserQuery();
    }

    public function test_can_search_by_name(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $results = $this->userQuery->reset()->search('John')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Doe');
    }

    public function test_can_search_by_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);

        $results = $this->userQuery->reset()->search('john@example.com')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->email)->toBe('john@example.com');
    }

    public function test_can_search_by_username(): void
    {
        User::factory()->create(['username' => 'johndoe']);
        User::factory()->create(['username' => 'janesmith']);

        $results = $this->userQuery->reset()->search('johndoe')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->username)->toBe('johndoe');
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        User::factory()->create(['name' => 'John Doe']);

        $results = $this->userQuery->reset()->search('Nonexistent')->get();

        expect($results)->toHaveCount(0);
    }

    public function test_search_ignores_empty_string(): void
    {
        User::factory()->count(3)->create();

        $results = $this->userQuery->reset()->search('')->get();

        expect($results)->toHaveCount(3);
    }

    public function test_can_sort_by_column_ascending(): void
    {
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $results = $this->userQuery->reset()->sort('name', 'asc')->get();

        $names = $results->pluck('name')->toArray();
        expect($names)->toBe(['Alice', 'Bob', 'Charlie']);
    }

    public function test_can_sort_by_column_descending(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Charlie']);

        $results = $this->userQuery->reset()->sort('name', 'desc')->get();

        $names = $results->pluck('name')->toArray();
        expect($names)->toBe(['Charlie', 'Bob', 'Alice']);
    }

    public function test_sort_without_column_uses_latest(): void
    {
        User::factory()->create(['created_at' => now()->subDays(5)]);
        $newUser = User::factory()->create(['created_at' => now()]);

        $results = $this->userQuery->reset()->sort(null)->get();

        expect($results->first()->id)->toBe($newUser->id);
    }

    public function test_can_filter_verified_users(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);
        User::factory()->create(['email_verified_at' => now()]);

        $results = $this->userQuery->reset()->verified()->get();

        expect($results)->toHaveCount(2)
            ->and($results->every(fn ($user): bool => $user->email_verified_at !== null))->toBeTrue();
    }

    public function test_can_filter_active_users(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);
        $deleted = User::factory()->create(['email_verified_at' => now()]);
        $deleted->delete();

        $results = $this->userQuery->reset()->activeUsers()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->email_verified_at)->not->toBeNull()
            ->and($results->first()->deleted_at)->toBeNull();
    }

    public function test_can_filter_recent_users(): void
    {
        User::factory()->create(['created_at' => now()->subDays(10)]);
        User::factory()->create(['created_at' => now()->subDays(3)]);
        User::factory()->create(['created_at' => now()]);

        $results = $this->userQuery->reset()->recentUsers(7)->get();

        expect($results)->toHaveCount(2);
    }

    public function test_can_limit_results(): void
    {
        User::factory()->count(10)->create();

        $results = $this->userQuery->reset()->limit(5)->get();

        expect($results)->toHaveCount(5);
    }

    public function test_can_paginate_results(): void
    {
        User::factory()->count(25)->create();

        $paginator = $this->userQuery->reset()->paginate(10);

        expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginator->perPage())->toBe(10)
            ->and($paginator->total())->toBe(25);
    }

    public function test_can_find_by_username(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $found = $this->userQuery->reset()->findByUsername('testuser');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($user->id);
    }

    public function test_find_by_username_returns_null_when_not_found(): void
    {
        $found = $this->userQuery->reset()->findByUsername('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_can_find_by_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $found = $this->userQuery->reset()->findByEmail('test@example.com');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($user->id);
    }

    public function test_find_by_email_returns_null_when_not_found(): void
    {
        $found = $this->userQuery->reset()->findByEmail('nonexistent@example.com');

        expect($found)->toBeNull();
    }

    public function test_reset_clears_query(): void
    {
        User::factory()->count(3)->create();

        $this->userQuery->search('test')->sort('name');

        $resetQuery = $this->userQuery->reset();

        expect($resetQuery)->toBeInstanceOf(UserQuery::class);

        $allUsers = $resetQuery->get();
        expect($allUsers->count())->toBe(3);
    }

    public function test_can_chain_methods(): void
    {
        User::factory()->create(['name' => 'John Verified', 'email_verified_at' => now()]);
        User::factory()->create(['name' => 'John Unverified', 'email_verified_at' => null]);
        User::factory()->create(['name' => 'Jane Verified', 'email_verified_at' => now()]);

        $results = $this->userQuery
            ->reset()
            ->search('John')
            ->verified()
            ->sort('name', 'asc')
            ->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Verified');
    }
}
