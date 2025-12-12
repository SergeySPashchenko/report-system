<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Company;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ExpenseQuery
{
    /**
     * @var Builder<Expense>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Expense::query();
    }

    /**
     * Filter expenses by user access.
     */
    public function forUser(User $user): self
    {
        // Користувач компанії має доступ до всього
        if ($user->company() instanceof Company) {
            return $this;
        }

        // Користувач з доступами по продуктам має доступ до expenses цих продуктів
        if ($user->products()->exists()) {
            $productIds = $user->products()->pluck('products.id')->toArray();
            $this->query->whereHas('product', function (Builder $q) use ($productIds): void {
                $q->whereIn('products.id', $productIds);
            });

            return $this;
        }

        // Користувач з доступами по брендам має доступ до expenses продуктів цих брендів
        if ($user->brands()->exists()) {
            $brandIds = $user->brands()->pluck('brands.id')->toArray();
            $this->query->whereHas('product', function (Builder $q) use ($brandIds): void {
                $q->whereIn('brand_id', $brandIds);
            });

            return $this;
        }

        // Якщо немає доступу, повертаємо порожній результат
        $this->query->whereRaw('1 = 0');

        return $this;
    }

    /**
     * Filter by product ID.
     */
    public function byProduct(int $productId): self
    {
        $this->query->where('ProductID', $productId);

        return $this;
    }

    /**
     * Filter by expensetype ID.
     */
    public function byExpensetype(int $expensetypeId): self
    {
        $this->query->where('ExpenseID', $expensetypeId);

        return $this;
    }

    /**
     * Filter by brand ID (through product relationship).
     */
    public function byBrand(string $brandId): self
    {
        $this->query->whereHas('product', function (Builder $q) use ($brandId): void {
            $q->where('brand_id', $brandId);
        });

        return $this;
    }

    /**
     * Filter by category ID (through product relationship - main or marketing).
     */
    public function byCategory(string $categoryId): self
    {
        $this->query->whereHas('product', function (Builder $q) use ($categoryId): void {
            $q->where(function (Builder $subQ) use ($categoryId): void {
                $subQ->where('main_category_id', $categoryId)
                    ->orWhere('marketing_category_id', $categoryId);
            });
        });

        return $this;
    }

    /**
     * Filter by gender ID (through product relationship).
     */
    public function byGender(string $genderId): self
    {
        $this->query->whereHas('product', function (Builder $q) use ($genderId): void {
            $q->where('gender_id', $genderId);
        });

        return $this;
    }

    /**
     * Filter by date range.
     */
    public function byDateRange(?string $startDate, ?string $endDate): self
    {
        if ($startDate !== null) {
            $this->query->whereDate('ExpenseDate', '>=', $startDate);
        }
        if ($endDate !== null) {
            $this->query->whereDate('ExpenseDate', '<=', $endDate);
        }

        return $this;
    }

    /**
     * Filter by specific date.
     */
    public function byDate(string $date): self
    {
        $this->query->whereDate('ExpenseDate', $date);

        return $this;
    }

    /**
     * Sort expenses by column and direction.
     */
    public function sort(?string $column, string $direction = 'asc'): self
    {
        if ($column !== null && $column !== '') {
            $this->query->orderBy($column, $direction);
        } else {
            $this->query->latest('ExpenseDate')->latest('created_at');
        }

        return $this;
    }

    /**
     * Limit number of results.
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);

        return $this;
    }

    /**
     * Paginate results.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Expense> $paginator */
        $paginator = $this->query->with(['product', 'expensetype'])->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Expense>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Expense> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Expense>
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Reset query to start fresh.
     */
    public function reset(): self
    {
        $this->query = Expense::query();

        return $this;
    }
}
