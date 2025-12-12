<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Access;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Product;
use App\Models\User;
use App\Queries\ExpenseQuery;
use App\Services\ExpenseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseService $expenseService;

    private ExpenseQuery $expenseQuery;

    private User $user;

    private Product $product;

    private Expensetype $expensetype;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expenseQuery = new ExpenseQuery();
        $this->expenseService = new ExpenseService($this->expenseQuery);
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['ProductID' => 12345]);
        $this->expensetype = Expensetype::factory()->create(['ExpenseTypeID' => 1]);

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->product->id,
            'accessible_type' => 'product',
        ]);
    }

    public function test_can_get_paginated_expenses(): void
    {
        Expense::factory()->count(20)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->getPaginatedExpenses($this->user);

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBeGreaterThan(0)
            ->and($result->perPage())->toBe(15);
    }

    public function test_can_filter_expenses_by_product(): void
    {
        $otherProduct = Product::factory()->create(['ProductID' => 99999]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->getPaginatedExpenses($this->user, productId: $this->product->ProductID);

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($this->product->ProductID);
    }

    public function test_can_filter_expenses_by_expensetype(): void
    {
        $otherExpensetype = Expensetype::factory()->create(['ExpenseTypeID' => 2]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $otherExpensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->getPaginatedExpenses($this->user, expensetypeId: $this->expensetype->ExpenseTypeID);

        expect($result->count())->toBe(1)
            ->and($result->first()->ExpenseID)->toBe($this->expensetype->ExpenseTypeID);
    }

    public function test_can_filter_expenses_by_date_range(): void
    {
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-01',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-02',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-03',
        ]);

        $result = $this->expenseService->getPaginatedExpenses($this->user, startDate: '2022-07-02', endDate: '2022-07-02');

        expect($result->count())->toBe(1)
            ->and($result->first()->ExpenseDate->format('Y-m-d'))->toBe('2022-07-02');
    }

    public function test_can_sort_expenses(): void
    {
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 100.00,
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 200.00,
        ]);

        $result = $this->expenseService->getPaginatedExpenses($this->user, sortBy: 'Expense', sortDirection: 'asc');

        $expenses = $result->pluck('Expense')->toArray();
        expect($expenses[0])->toBeLessThanOrEqual($expenses[1] ?? $expenses[0]);
    }

    public function test_can_get_paginated_expenses_for_product(): void
    {
        Expense::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->getPaginatedExpensesForProduct($this->user, $this->product->ProductID);

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(5);
    }

    public function test_can_get_paginated_expenses_for_expensetype(): void
    {
        Expense::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->getPaginatedExpensesForExpensetype($this->user, $this->expensetype->ExpenseTypeID);

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBe(5);
    }

    public function test_can_create_expense(): void
    {
        $data = [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-05',
            'Expense' => 150.75,
        ];

        $expense = $this->expenseService->create($data);

        expect($expense)->toBeInstanceOf(Expense::class)
            ->and($expense->ProductID)->toBe($this->product->ProductID);

        $this->assertEquals(150.75, (float) $expense->Expense);

        $this->assertDatabaseHas('expenses', [
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 150.75,
        ]);
    }

    public function test_can_update_expense(): void
    {
        $expense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 100.00,
        ]);

        $updated = $this->expenseService->update($expense, ['Expense' => 200.00]);

        $this->assertEquals(200.00, (float) $updated->Expense);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'Expense' => 200.00,
        ]);
    }

    public function test_can_delete_expense(): void
    {
        $expense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->delete($expense);

        expect($result)->toBeTrue();

        $this->assertSoftDeleted('expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_can_restore_expense(): void
    {
        $expense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $expense->delete();

        $restored = $this->expenseService->restore($expense->id);

        expect($restored->id)->toBe($expense->id);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_expense(): void
    {
        $expense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $expenseId = $expense->id;
        $expense->delete();

        $result = $this->expenseService->forceDelete($expenseId);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('expenses', [
            'id' => $expenseId,
        ]);
    }

    public function test_can_get_statistics(): void
    {
        Expense::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $deleted = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        $deleted->delete();

        $stats = $this->expenseService->getStatistics($this->user);

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['total', 'deleted', 'total_amount', 'created_today', 'created_this_week', 'created_this_month'])
            ->and($stats['total'])->toBeGreaterThan(0)
            ->and($stats['deleted'])->toBe(1);
    }

    public function test_company_user_has_access_to_all_expenses(): void
    {
        $company = Company::factory()->create();
        $companyUser = User::factory()->create();

        Access::factory()->create([
            'user_id' => $companyUser->id,
            'accessible_id' => $company->id,
            'accessible_type' => 'company',
        ]);

        Expense::factory()->count(10)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseService->getPaginatedExpenses($companyUser);

        expect($result->count())->toBe(10);
    }
}
