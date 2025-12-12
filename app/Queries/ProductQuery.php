<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProductQuery
{
    /**
     * @var Builder<Product>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Product::query();
    }

    /**
     * Filter products by user access.
     */
    public function forUser(User $user): self
    {
        // Користувач компанії має доступ до всього
        if ($user->company() instanceof Company) {
            return $this;
        }

        // Користувач з доступами по продуктам має доступ до всього по продуктах
        if ($user->products()->exists()) {
            $productIds = $user->products()->pluck('id')->toArray();
            $this->query->whereIn('id', $productIds);

            return $this;
        }

        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        if ($user->brands()->exists()) {
            $brandIds = $user->brands()->pluck('id')->toArray();
            $this->query->whereIn('brand_id', $brandIds);

            return $this;
        }

        // Якщо немає доступу, повертаємо порожній результат
        $this->query->whereRaw('1 = 0');

        return $this;
    }

    /**
     * Search products by name or slug.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('Product', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Sort products by column and direction.
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
     * Filter by brand ID.
     */
    public function byBrand(string $brandId): self
    {
        $this->query->where('brand_id', $brandId);

        return $this;
    }

    /**
     * Filter by category ID.
     */
    public function byCategory(string $categoryId): self
    {
        $this->query->where(function (Builder $q) use ($categoryId): void {
            $q->where('main_category_id', $categoryId)
                ->orWhere('marketing_category_id', $categoryId);
        });

        return $this;
    }

    /**
     * Filter by gender ID.
     */
    public function byGender(string $genderId): self
    {
        $this->query->where('gender_id', $genderId);

        return $this;
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
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Product> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Product>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Product> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find product by slug.
     */
    public function findBySlug(string $slug): ?Product
    {
        /** @var Product|null $product */
        $product = $this->query->where('slug', $slug)->first();

        return $product;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Product>
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
        $this->query = Product::query();

        return $this;
    }
}
