<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class BrandQuery
{
    /**
     * @var Builder<Brand>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Brand::query();
    }

    /**
     * Search brands by name or slug.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Sort brands by column and direction.
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
     * Filter by brand name.
     */
    public function byName(string $name): self
    {
        $this->query->where('name', $name);

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
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Brand> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Brand>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Brand> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find brand by slug.
     */
    public function findBySlug(string $slug): ?Brand
    {
        /** @var Brand|null $brand */
        $brand = $this->query->where('slug', $slug)->first();

        return $brand;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Brand>
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
        $this->query = Brand::query();

        return $this;
    }
}
