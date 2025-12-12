<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Queries\CompanyQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CompanyService
{
    public function __construct(
        private CompanyQuery $companyQuery
    ) {}

    /**
     * Get paginated companies with filters.
     *
     * @return LengthAwarePaginator<int, Company>
     */
    public function getPaginatedCompanies(
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->companyQuery
            ->reset()
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find company by slug.
     */
    public function findBySlug(string $slug): ?Company
    {
        return $this->companyQuery->findBySlug($slug);
    }

    /**
     * Create a new company.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        /** @var array{name: string} $data */
        return Company::query()->create([
            'name' => $data['name'],
        ]);
    }

    /**
     * Update company.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $company->update($updateData);

        $fresh = $company->fresh();

        assert($fresh instanceof Company);

        return $fresh;
    }

    /**
     * Delete company (soft delete).
     */
    public function delete(Company $company): bool
    {
        $result = $company->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted company.
     */
    public function restore(string $id): Company
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->restore();

        return $company;
    }

    /**
     * Permanently delete company.
     */
    public function forceDelete(string $id): bool
    {
        $company = Company::withTrashed()->findOrFail($id);

        $result = $company->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get company statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Company::query()->count(),
            'deleted' => Company::onlyTrashed()->count(),
            'created_today' => Company::query()->whereDate('created_at', today())->count(),
            'created_this_week' => Company::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => Company::query()->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
