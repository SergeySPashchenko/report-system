<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\ProductItem;
use App\Models\User;
use App\Queries\ProductItemQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ProductItemService
{
    public function __construct(
        private ProductItemQuery $productItemQuery
    ) {}

    /**
     * Get paginated product items with filters.
     *
     * @return LengthAwarePaginator<int, ProductItem>
     */
    public function getPaginatedProductItems(
        User $user,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?bool $active = null,
        ?bool $deleted = null,
        ?bool $upSell = null,
        ?bool $extraProduct = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->productItemQuery
            ->reset()
            ->forUser($user)
            ->search($search)
            ->active($active)
            ->deleted($deleted)
            ->upSell($upSell)
            ->extraProduct($extraProduct);

        return $query->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated product items for a specific product.
     *
     * @return LengthAwarePaginator<int, ProductItem>
     */
    public function getPaginatedProductItemsForProduct(
        User $user,
        int $productId,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?bool $active = null,
        ?bool $deleted = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productItemQuery
            ->reset()
            ->forUser($user)
            ->byProduct($productId)
            ->search($search)
            ->active($active)
            ->deleted($deleted)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated product items for a specific brand.
     *
     * @return LengthAwarePaginator<int, ProductItem>
     */
    public function getPaginatedProductItemsForBrand(
        User $user,
        string $brandId,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?bool $active = null,
        ?bool $deleted = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productItemQuery
            ->reset()
            ->forUser($user)
            ->byBrand($brandId)
            ->search($search)
            ->active($active)
            ->deleted($deleted)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated product items for a specific category.
     *
     * @return LengthAwarePaginator<int, ProductItem>
     */
    public function getPaginatedProductItemsForCategory(
        User $user,
        string $categoryId,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?bool $active = null,
        ?bool $deleted = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productItemQuery
            ->reset()
            ->forUser($user)
            ->byCategory($categoryId)
            ->search($search)
            ->active($active)
            ->deleted($deleted)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Get paginated product items for a specific gender.
     *
     * @return LengthAwarePaginator<int, ProductItem>
     */
    public function getPaginatedProductItemsForGender(
        User $user,
        string $genderId,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        ?bool $active = null,
        ?bool $deleted = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->productItemQuery
            ->reset()
            ->forUser($user)
            ->byGender($genderId)
            ->search($search)
            ->active($active)
            ->deleted($deleted)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find product item by slug.
     */
    public function findBySlug(string $slug): ?ProductItem
    {
        return $this->productItemQuery->findBySlug($slug);
    }

    /**
     * Create a new product item.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductItem
    {
        /** @var array{ItemID?: int|null, ProductID: int, ProductName: string, SKU: string, Quantity: int, upSell?: bool, extraProduct?: bool, offerProducts?: string|null, active?: bool, deleted?: bool} $data */
        return ProductItem::query()->create($data);
    }

    /**
     * Update product item.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProductItem $productItem, array $data): ProductItem
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $productItem->update($updateData);

        $fresh = $productItem->fresh();

        assert($fresh instanceof ProductItem);

        return $fresh;
    }

    /**
     * Delete product item (soft delete).
     */
    public function delete(ProductItem $productItem): bool
    {
        $result = $productItem->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted product item.
     */
    public function restore(string $id): ProductItem
    {
        $productItem = ProductItem::withTrashed()->findOrFail($id);
        $productItem->restore();

        return $productItem;
    }

    /**
     * Permanently delete product item.
     */
    public function forceDelete(string $id): bool
    {
        $productItem = ProductItem::withTrashed()->findOrFail($id);

        $result = $productItem->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get product item statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(User $user): array
    {
        $baseQuery = ProductItem::query();

        // Фільтруємо за доступом користувача
        if (! $user->company() instanceof Company) {
            if ($user->products()->exists()) {
                $productIds = $user->products()->pluck('products.ProductID')->toArray();
                if (! empty($productIds)) {
                    $baseQuery->whereIn('ProductID', $productIds);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            } elseif ($user->brands()->exists()) {
                $brandIds = $user->brands()->pluck('brands.id')->toArray();
                if (! empty($brandIds)) {
                    $baseQuery->whereHas('product', function ($q) use ($brandIds): void {
                        $q->whereIn('brand_id', $brandIds);
                    });
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        return [
            'total' => $baseQuery->count(),
            'deleted' => ProductItem::onlyTrashed()->count(),
            'active' => (clone $baseQuery)->where('active', true)->count(),
            'inactive' => (clone $baseQuery)->where('active', false)->count(),
            'upSell' => (clone $baseQuery)->where('upSell', true)->count(),
            'extraProduct' => (clone $baseQuery)->where('extraProduct', true)->count(),
            'created_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'created_this_week' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
