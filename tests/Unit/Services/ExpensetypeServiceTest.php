<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Expensetype;
use App\Queries\ExpensetypeQuery;
use App\Services\ExpensetypeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpensetypeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpensetypeService $expensetypeService;

    private ExpensetypeQuery $expensetypeQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expensetypeQuery = new ExpensetypeQuery();
        $this->expensetypeService = new ExpensetypeService($this->expensetypeQuery);
    }

    public function test_can_get_paginated_expensetypes(): void
    {
        Expensetype::factory()->count(20)->create();

        $result = $this->expensetypeService->getPaginatedExpensetypes();

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBeGreaterThan(0)
            ->and($result->perPage())->toBe(15);
    }

    public function test_can_search_expensetypes(): void
    {
        Expensetype::factory()->create(['Name' => 'Marketing']);
        Expensetype::factory()->create(['Name' => 'Sales']);

        $result = $this->expensetypeService->getPaginatedExpensetypes(search: 'Marketing');

        expect($result->count())->toBe(1)
            ->and($result->first()->Name)->toBe('Marketing');
    }

    public function test_can_sort_expensetypes(): void
    {
        Expensetype::factory()->create(['Name' => 'Charlie']);
        Expensetype::factory()->create(['Name' => 'Alpha']);
        Expensetype::factory()->create(['Name' => 'Beta']);

        $result = $this->expensetypeService->getPaginatedExpensetypes(sortBy: 'Name', sortDirection: 'asc');

        $names = $result->pluck('Name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_can_find_expensetype_by_slug(): void
    {
        $expensetype = Expensetype::factory()->create(['Name' => 'Test ExpenseType']);

        $found = $this->expensetypeService->findBySlug($expensetype->slug);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($expensetype->id)
            ->and($found->slug)->toBe($expensetype->slug);
    }

    public function test_returns_null_when_slug_not_found(): void
    {
        $found = $this->expensetypeService->findBySlug('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_can_create_expensetype(): void
    {
        $data = [
            'Name' => 'New ExpenseType',
        ];

        $expensetype = $this->expensetypeService->create($data);

        expect($expensetype)->toBeInstanceOf(Expensetype::class)
            ->and($expensetype->Name)->toBe('New ExpenseType');

        $this->assertDatabaseHas('expensetypes', [
            'Name' => 'New ExpenseType',
        ]);
    }

    public function test_can_create_expensetype_with_expensetype_id(): void
    {
        $data = [
            'Name' => 'New ExpenseType',
            'ExpenseTypeID' => 999,
        ];

        $expensetype = $this->expensetypeService->create($data);

        expect($expensetype)->toBeInstanceOf(Expensetype::class)
            ->and($expensetype->Name)->toBe('New ExpenseType')
            ->and($expensetype->ExpenseTypeID)->toBe(999);
    }

    public function test_can_update_expensetype(): void
    {
        $expensetype = Expensetype::factory()->create(['Name' => 'Old Name']);

        $updated = $this->expensetypeService->update($expensetype, ['Name' => 'New Name']);

        expect($updated->Name)->toBe('New Name');

        $this->assertDatabaseHas('expensetypes', [
            'id' => $expensetype->id,
            'Name' => 'New Name',
        ]);
    }

    public function test_can_delete_expensetype(): void
    {
        $expensetype = Expensetype::factory()->create();

        $result = $this->expensetypeService->delete($expensetype);

        expect($result)->toBeTrue();

        $this->assertSoftDeleted('expensetypes', [
            'id' => $expensetype->id,
        ]);
    }

    public function test_can_restore_expensetype(): void
    {
        $expensetype = Expensetype::factory()->create();
        $expensetype->delete();

        $restored = $this->expensetypeService->restore($expensetype->id);

        expect($restored->id)->toBe($expensetype->id);

        $this->assertDatabaseHas('expensetypes', [
            'id' => $expensetype->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_expensetype(): void
    {
        $expensetype = Expensetype::factory()->create();
        $expensetypeId = $expensetype->id;
        $expensetype->delete();

        $result = $this->expensetypeService->forceDelete($expensetypeId);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('expensetypes', [
            'id' => $expensetypeId,
        ]);
    }

    public function test_can_get_statistics(): void
    {
        Expensetype::factory()->count(5)->create();
        $deleted = Expensetype::factory()->create();
        $deleted->delete();

        $stats = $this->expensetypeService->getStatistics();

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['total', 'deleted', 'created_today', 'created_this_week', 'created_this_month'])
            ->and($stats['total'])->toBeGreaterThan(0)
            ->and($stats['deleted'])->toBe(1);
    }
}
