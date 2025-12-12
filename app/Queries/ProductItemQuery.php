<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Company;
use App\Models\ProductItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProductItemQuery
{
    /**
     * @var Builder<ProductItem>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = ProductItem::query();
    }

    /**
     * Filter product items by user access.
     */
    public function forUser(User $user): self
    {
        // Користувач компанії має доступ до всього
        if ($user->company() instanceof Company) {
            return $this;
        }

        // Користувач з доступами по продуктам має доступ до product items цих продуктів
        if ($user->products()->exists()) {
            $productIds = $user->products()->pluck('products.ProductID')->toArray();
            if (! empty($productIds)) {
                $this->query->whereIn('ProductID', $productIds);
            }

            return $this;
        }

        // Користувач з доступами по брендам має доступ до product items продуктів цих брендів
        if ($user->brands()->exists()) {
            $brandIds = $user->brands()->pluck('brands.id')->toArray();
            if (! empty($brandIds)) {
                $this->query->whereHas('product', function (Builder $q) use ($brandIds): void {
                    $q->whereIn('brand_id', $brandIds);
                });
            }

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
     * Search product items by name, SKU, or slug.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('ProductName', 'like', "%{$search}%")
                    ->orWhere('SKU', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Filter by active status.
     */
    public function active(?bool $active = true): self
    {
        if ($active !== null) {
            $this->query->where('active', $active ? 1 : 0);
        }

        return $this;
    }

    /**
     * Filter by deleted status.
     */
    public function deleted(?bool $deleted = true): self
    {
        if ($deleted !== null) {
            $this->query->where('deleted', $deleted);
        }

        return $this;
    }

    /**
     * Filter by upSell status.
     */
    public function upSell(?bool $upSell = true): self
    {
        if ($upSell !== null) {
            $this->query->where('upSell', $upSell ? 1 : 0);
        }

        return $this;
    }

    /**
     * Filter by extraProduct status.
     */
    public function extraProduct(?bool $extraProduct = true): self
    {
        if ($extraProduct !== null) {
            $this->query->where('extraProduct', $extraProduct ? 1 : 0);
        }

        return $this;
    }

    /**
     * Sort product items by column and direction.
     */
    public function sort(?string $column, string $direction = 'asc'): self
    {
        if ($column !== null && $column !== '') {
            $this->query->orderBy($column, $direction);
        } else {
            $this->query->latest('created_at');
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
     * @return LengthAwarePaginator<int, ProductItem>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, ProductItem> $paginator */
        $paginator = $this->query->with(['product'])->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, ProductItem>
     */
    public function get(): Collection
    {
        /** @var Collection<int, ProductItem> $collection */
        $collection = $this->query->with(['product'])->get();

        return $collection;
    }

    /**
     * Filter by slug.
     */
    public function bySlug(string $slug): self
    {
        $this->query->where('slug', $slug);

        return $this;
    }

    /**
     * Find product item by slug.
     */
    public function findBySlug(string $slug): ?ProductItem
    {
        /** @var ProductItem|null $productItem */
        $productItem = $this->query->where('slug', $slug)->first();

        return $productItem;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<ProductItem>
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
        $this->query = ProductItem::query();

        return $this;
    }
}
