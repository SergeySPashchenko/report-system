<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use App\Queries\ProductQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ProductService
{
    public function __construct(
        private ProductQuery $productQuery
    ) {}

    /**
     * Get paginated products with filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProducts(
        User $user,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productQuery
            ->reset()
            ->forUser($user)
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find product by slug.
     */
    public function findBySlug(User $user, string $slug): ?Product
    {
        return $this->productQuery
            ->reset()
            ->forUser($user)
            ->findBySlug($slug);
    }

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        /** @var array<string, mixed> $data */
        return Product::query()->create($data);
    }

    /**
     * Update product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $product->update($updateData);

        $fresh = $product->fresh();

        assert($fresh instanceof Product);

        return $fresh;
    }

    /**
     * Delete product (soft delete).
     */
    public function delete(Product $product): bool
    {
        $result = $product->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted product.
     */
    public function restore(string $id): Product
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return $product;
    }

    /**
     * Permanently delete product.
     */
    public function forceDelete(string $id): bool
    {
        $product = Product::withTrashed()->findOrFail($id);

        $result = $product->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get paginated products for a specific brand.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProductsForBrand(
        User $user,
        Brand $brand,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productQuery
            ->reset()
            ->forUser($user)
            ->byBrand((string) $brand->id)
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated products for a specific category (both main and marketing).
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProductsForCategory(
        User $user,
        Category $category,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productQuery
            ->reset()
            ->forUser($user)
            ->byCategory((string) $category->id)
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated products for a specific gender.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProductsForGender(
        User $user,
        Gender $gender,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productQuery
            ->reset()
            ->forUser($user)
            ->byGender((string) $gender->id)
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get product statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(User $user): array
    {
        $baseQuery = Product::query();

        // Фільтруємо за доступом користувача
        if (! $user->company() instanceof Company) {
            if ($user->products()->exists()) {
                $productIds = $user->products()->pluck('id')->toArray();
                $baseQuery->whereIn('id', $productIds);
            } elseif ($user->brands()->exists()) {
                $brandIds = $user->brands()->pluck('id')->toArray();
                $baseQuery->whereIn('brand_id', $brandIds);
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
            'created_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'created_this_week' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
