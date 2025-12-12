<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class CompanyQuery
{
    /**
     * @var Builder<Company>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Company::query();
    }

    /**
     * Search companies by name or slug.
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
     * Sort companies by column and direction.
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
     * Filter by company name.
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
     * @return LengthAwarePaginator<int, Company>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Company> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, Company>
     */
    public function get(): Collection
    {
        /** @var Collection<int, Company> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find company by slug.
     */
    public function findBySlug(string $slug): ?Company
    {
        /** @var Company|null $company */
        $company = $this->query->where('slug', $slug)->first();

        return $company;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<Company>
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
        $this->query = Company::query();

        return $this;
    }
}
