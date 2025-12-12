<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class CategoryQuery
{
    /**
     * @var Builder<Category>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Category::query();
    }

    /**
     * Search categories by name or slug.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('category_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Sort categories by column and direction.
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
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Category> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Category>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Category> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        /** @var Category|null $category */
        $category = $this->query->where('slug', $slug)->first();

        return $category;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Category>
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
        $this->query = Category::query();

        return $this;
    }
}
