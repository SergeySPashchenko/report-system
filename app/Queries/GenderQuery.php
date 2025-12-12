<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Gender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class GenderQuery
{
    /**
     * @var Builder<Gender>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Gender::query();
    }

    /**
     * Search genders by name or slug.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('gender_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Sort genders by column and direction.
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
     * @return LengthAwarePaginator<int, Gender>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Gender> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Gender>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Gender> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find gender by slug.
     */
    public function findBySlug(string $slug): ?Gender
    {
        /** @var Gender|null $gender */
        $gender = $this->query->where('slug', $slug)->first();

        return $gender;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Gender>
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
        $this->query = Gender::query();

        return $this;
    }
}
