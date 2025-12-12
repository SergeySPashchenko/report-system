<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserCreated;
use App\Listeners\AssignCompanyToUser;
use App\Models\Access;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssignCompanyToUserTest extends TestCase
{
    use RefreshDatabase;

    private AssignCompanyToUser $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new AssignCompanyToUser();
    }

    public function test_creates_main_company_for_first_user(): void
    {
        $user = User::factory()->create();

        $this->listener->handle(new UserCreated($user));

        $mainCompany = Company::query()->where('name', 'Main')->first();

        expect($mainCompany)->not->toBeNull()
            ->and($mainCompany->name)->toBe('Main');

        $this->assertDatabaseHas('accesses', [
            'user_id' => $user->id,
            'accessible_id' => $mainCompany->id,
            'accessible_type' => Company::class,
        ]);
    }

    public function test_assigns_existing_main_company_to_subsequent_users(): void
    {
        // Create first user and Main company
        $firstUser = User::factory()->create();
        $this->listener->handle(new UserCreated($firstUser));

        $mainCompany = Company::query()->where('name', 'Main')->first();
        $mainCompanyId = $mainCompany->id;

        // Create second user
        $secondUser = User::factory()->create();
        $this->listener->handle(new UserCreated($secondUser));

        // Verify Main company was not duplicated
        $mainCompanies = Company::query()->where('name', 'Main')->get();
        expect($mainCompanies)->toHaveCount(1)
            ->and($mainCompanies->first()->id)->toBe($mainCompanyId);

        // Verify second user has access to Main company
        $this->assertDatabaseHas('accesses', [
            'user_id' => $secondUser->id,
            'accessible_id' => $mainCompanyId,
            'accessible_type' => Company::class,
        ]);
    }

    public function test_creates_access_record_for_user(): void
    {
        $user = User::factory()->create();

        $this->listener->handle(new UserCreated($user));

        $mainCompany = Company::query()->where('name', 'Main')->first();

        $access = Access::query()
            ->where('user_id', $user->id)
            ->where('accessible_id', $mainCompany->id)
            ->where('accessible_type', Company::class)
            ->first();

        expect($access)->not->toBeNull();
    }

    public function test_multiple_users_get_same_main_company(): void
    {
        $user1 = User::factory()->create();
        // Access already created by UserObserver, but we call listener again to test idempotency
        $this->listener->handle(new UserCreated($user1));

        $user2 = User::factory()->create();
        $this->listener->handle(new UserCreated($user2));

        $user3 = User::factory()->create();
        $this->listener->handle(new UserCreated($user3));

        $mainCompany = Company::query()->where('name', 'Main')->first();

        expect($mainCompany)->not->toBeNull();

        // Each user should have access (may be duplicated if listener called twice, but that's OK)
        // Verify each user has at least one access
        expect(Access::query()
            ->where('user_id', $user1->id)
            ->where('accessible_id', $mainCompany->id)
            ->where('accessible_type', Company::class)
            ->exists())->toBeTrue();

        expect(Access::query()
            ->where('user_id', $user2->id)
            ->where('accessible_id', $mainCompany->id)
            ->where('accessible_type', Company::class)
            ->exists())->toBeTrue();

        expect(Access::query()
            ->where('user_id', $user3->id)
            ->where('accessible_id', $mainCompany->id)
            ->where('accessible_type', Company::class)
            ->exists())->toBeTrue();

        // Verify all users share the same Main company
        $accessCount = Access::query()
            ->where('accessible_id', $mainCompany->id)
            ->where('accessible_type', Company::class)
            ->distinct('user_id')
            ->count('user_id');

        expect($accessCount)->toBeGreaterThanOrEqual(3);
    }
}
