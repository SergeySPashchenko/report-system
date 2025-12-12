<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Expense;
use App\Models\User;
use App\Queries\ExpenseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ExpenseService
{
    public function __construct(
        private ExpenseQuery $expenseQuery
    ) {}

    /**
     * Get paginated expenses with filters.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function getPaginatedExpenses(
        User $user,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $productId = null,
        ?int $expensetypeId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->expenseQuery
            ->reset()
            ->forUser($user)
            ->byDateRange($startDate, $endDate);

        if ($productId !== null) {
            $query->byProduct($productId);
        }

        if ($expensetypeId !== null) {
            $query->byExpensetype($expensetypeId);
        }

        return $query->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated expenses for a specific product.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function getPaginatedExpensesForProduct(
        User $user,
        int $productId,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->expenseQuery
            ->reset()
            ->forUser($user)
            ->byProduct($productId)
            ->byDateRange($startDate, $endDate)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated expenses for a specific expensetype.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function getPaginatedExpensesForExpensetype(
        User $user,
        int $expensetypeId,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->expenseQuery
            ->reset()
            ->forUser($user)
            ->byExpensetype($expensetypeId)
            ->byDateRange($startDate, $endDate)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated expenses for a specific brand.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function getPaginatedExpensesForBrand(
        User $user,
        string $brandId,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->expenseQuery
            ->reset()
            ->forUser($user)
            ->byBrand($brandId)
            ->byDateRange($startDate, $endDate)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated expenses for a specific category.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function getPaginatedExpensesForCategory(
        User $user,
        string $categoryId,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->expenseQuery
            ->reset()
            ->forUser($user)
            ->byCategory($categoryId)
            ->byDateRange($startDate, $endDate)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated expenses for a specific gender.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function getPaginatedExpensesForGender(
        User $user,
        string $genderId,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->expenseQuery
            ->reset()
            ->forUser($user)
            ->byGender($genderId)
            ->byDateRange($startDate, $endDate)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Create a new expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Expense
    {
        /** @var array{ProductID: int, ExpenseID: int, ExpenseDate: string, Expense: float|int|string, external_id?: int} $data */
        return Expense::query()->create($data);
    }

    /**
     * Update expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data): Expense
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $expense->update($updateData);

        $fresh = $expense->fresh();

        assert($fresh instanceof Expense);

        return $fresh;
    }

    /**
     * Delete expense (soft delete).
     */
    public function delete(Expense $expense): bool
    {
        $result = $expense->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted expense.
     */
    public function restore(string $id): Expense
    {
        $expense = Expense::withTrashed()->findOrFail($id);
        $expense->restore();

        return $expense;
    }

    /**
     * Permanently delete expense.
     */
    public function forceDelete(string $id): bool
    {
        $expense = Expense::withTrashed()->findOrFail($id);

        $result = $expense->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get expense statistics.
     *
     * @return array<string, int|float>
     */
    public function getStatistics(User $user): array
    {
        $baseQuery = Expense::query();

        // Фільтруємо за доступом користувача
        if (! $user->company() instanceof Company) {
            if ($user->products()->exists()) {
                $productIds = $user->products()->pluck('id')->toArray();
                $baseQuery->whereHas('product', function ($q) use ($productIds): void {
                    $q->whereIn('id', $productIds);
                });
            } elseif ($user->brands()->exists()) {
                $brandIds = $user->brands()->pluck('id')->toArray();
                $baseQuery->whereHas('product', function ($q) use ($brandIds): void {
                    $q->whereIn('brand_id', $brandIds);
                });
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        return [
            'total' => $baseQuery->count(),
            'deleted' => Expense::onlyTrashed()->count(),
            'total_amount' => (float) $baseQuery->sum('Expense'),
            'created_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'created_this_week' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
