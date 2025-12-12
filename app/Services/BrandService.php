<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use App\Queries\BrandQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class BrandService
{
    public function __construct(
        private BrandQuery $brandQuery
    ) {}

    /**
     * Get paginated brands with filters.
     *
     * @return LengthAwarePaginator<int, Brand>
     */
    public function getPaginatedBrands(
        User $user,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->brandQuery
            ->reset()
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc');

        // Фільтруємо за доступом користувача
        if (! $user->company() instanceof Company && $user->brands()->exists()) {
            $brandIds = $user->brands()->pluck('id')->toArray();
            $query->getQuery()->whereIn('id', $brandIds);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find brand by slug.
     */
    public function findBySlug(string $slug): ?Brand
    {
        return $this->brandQuery->findBySlug($slug);
    }

    /**
     * Create a new brand.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        /** @var array{name: string} $data */
        return Brand::query()->create([
            'name' => $data['name'],
        ]);
    }

    /**
     * Update brand.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $brand->update($updateData);

        $fresh = $brand->fresh();

        assert($fresh instanceof Brand);

        return $fresh;
    }

    /**
     * Delete brand (soft delete).
     */
    public function delete(Brand $brand): bool
    {
        $result = $brand->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted brand.
     */
    public function restore(string $id): Brand
    {
        $brand = Brand::withTrashed()->findOrFail($id);
        $brand->restore();

        return $brand;
    }

    /**
     * Permanently delete brand.
     */
    public function forceDelete(string $id): bool
    {
        $brand = Brand::withTrashed()->findOrFail($id);

        $result = $brand->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get brand statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(User $user): array
    {
        $baseQuery = Brand::query();

        // Фільтруємо за доступом користувача
        if (! $user->company() instanceof Company && $user->brands()->exists()) {
            $brandIds = $user->brands()->pluck('id')->toArray();
            $baseQuery->whereIn('id', $brandIds);
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
