<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Queries\CategoryQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CategoryService
{
    public function __construct(
        private CategoryQuery $categoryQuery
    ) {}

    /**
     * Get paginated categories with filters.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function getPaginatedCategories(
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->categoryQuery
            ->reset()
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->categoryQuery->findBySlug($slug);
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        /** @var array{category_name: string, category_id?: int} $data */
        return Category::query()->create([
            'category_name' => $data['category_name'],
            'category_id' => $data['category_id'] ?? null,
        ]);
    }

    /**
     * Update category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $category->update($updateData);

        $fresh = $category->fresh();

        assert($fresh instanceof Category);

        return $fresh;
    }

    /**
     * Delete category (soft delete).
     */
    public function delete(Category $category): bool
    {
        $result = $category->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted category.
     */
    public function restore(string $id): Category
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();

        return $category;
    }

    /**
     * Permanently delete category.
     */
    public function forceDelete(string $id): bool
    {
        $category = Category::withTrashed()->findOrFail($id);

        $result = $category->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get category statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Category::query()->count(),
            'deleted' => Category::onlyTrashed()->count(),
            'created_today' => Category::query()->whereDate('created_at', today())->count(),
            'created_this_week' => Category::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => Category::query()->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
