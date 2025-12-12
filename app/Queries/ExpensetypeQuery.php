<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Expensetype;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ExpensetypeQuery
{
    /**
     * @var Builder<Expensetype>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Expensetype::query();
    }

    /**
     * Search expensetypes by name or slug.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('Name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Sort expensetypes by column and direction.
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
     * @return LengthAwarePaginator<int, Expensetype>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Expensetype> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Expensetype>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Expensetype> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find expensetype by slug.
     */
    public function findBySlug(string $slug): ?Expensetype
    {
        /** @var Expensetype|null $expensetype */
        $expensetype = $this->query->where('slug', $slug)->first();

        return $expensetype;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Expensetype>
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
        $this->query = Expensetype::query();

        return $this;
    }
}
