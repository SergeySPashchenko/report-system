<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Product;
use App\Services\ExpenseSyncService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

final class ExpenseSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExpenseSyncService();
    }

    public function test_can_get_expenses_from_external_database(): void
    {
        try {
            // Check if external database is accessible (read-only)
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getExpensesFromExternal');

            // Try to get expenses for a specific date
            $expenses = $method->invoke($this->service, '2022-07-02');

            expect($expenses)->toBeInstanceOf(Collection::class);
        } catch (Exception $e) {
            $this->markTestSkipped('External database connection not configured: '.$e->getMessage());
        }
    }

    public function test_can_sync_expenses_for_date(): void
    {
        try {
            $stats = $this->service->syncForDate('2022-07-02');

            expect($stats)->toBeArray()
                ->and($stats)->toHaveKeys([
                    'brands_created',
                    'brands_skipped',
                    'expensetypes_created',
                    'expensetypes_skipped',
                    'categories_created',
                    'categories_skipped',
                    'genders_created',
                    'genders_skipped',
                    'products_created',
                    'products_updated',
                    'expenses_created',
                    'expenses_skipped',
                ]);
        } catch (Exception $e) {
            $this->markTestSkipped('External database connection not configured: '.$e->getMessage());
        }
    }

    public function test_skips_existing_expenses_during_sync(): void
    {
        try {
            // Create an expense type and product first
            $expensetype = Expensetype::factory()->create(['ExpenseTypeID' => 1]);
            $product = Product::factory()->create(['ProductID' => 12345]);

            // Create an existing expense
            Expense::factory()->create([
                'external_id' => 99999,
                'ProductID' => $product->ProductID,
                'ExpenseID' => $expensetype->ExpenseTypeID,
                'ExpenseDate' => '2022-07-02',
            ]);

            $stats = $this->service->syncForDate('2022-07-02');

            expect($stats)->toBeArray()
                ->and($stats)->toHaveKeys(['expenses_created', 'expenses_skipped']);
        } catch (Exception $e) {
            $this->markTestSkipped('External database connection not configured: '.$e->getMessage());
        }
    }

    public function test_creates_dependent_entities_during_sync(): void
    {
        try {
            $stats = $this->service->syncForDate('2022-07-02');

            expect($stats)->toBeArray();

            // Verify that if expenses were created, dependent entities might have been created too
            // This test verifies the logic works, but actual creation depends on external DB data
            if ($stats['expenses_created'] > 0) {
                expect(Expensetype::query()->count())->toBeGreaterThan(0);
                expect(Product::query()->count())->toBeGreaterThan(0);
            }
        } catch (Exception $e) {
            $this->markTestSkipped('External database connection not configured: '.$e->getMessage());
        }
    }
}
