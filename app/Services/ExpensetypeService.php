<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expensetype;
use App\Queries\ExpensetypeQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ExpensetypeService
{
    public function __construct(
        private ExpensetypeQuery $expensetypeQuery
    ) {}

    /**
     * Get paginated expensetypes with filters.
     *
     * @return LengthAwarePaginator<int, Expensetype>
     */
    public function getPaginatedExpensetypes(
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->expensetypeQuery
            ->reset()
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find expensetype by slug.
     */
    public function findBySlug(string $slug): ?Expensetype
    {
        return $this->expensetypeQuery->findBySlug($slug);
    }

    /**
     * Create a new expensetype.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Expensetype
    {
        /** @var array{Name: string, ExpenseTypeID?: int} $data */
        return Expensetype::query()->create([
            'Name' => $data['Name'],
            'ExpenseTypeID' => $data['ExpenseTypeID'] ?? null,
        ]);
    }

    /**
     * Update expensetype.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Expensetype $expensetype, array $data): Expensetype
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $expensetype->update($updateData);

        $fresh = $expensetype->fresh();

        assert($fresh instanceof Expensetype);

        return $fresh;
    }

    /**
     * Delete expensetype (soft delete).
     */
    public function delete(Expensetype $expensetype): bool
    {
        $result = $expensetype->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted expensetype.
     */
    public function restore(string $id): Expensetype
    {
        $expensetype = Expensetype::withTrashed()->findOrFail($id);
        $expensetype->restore();

        return $expensetype;
    }

    /**
     * Permanently delete expensetype.
     */
    public function forceDelete(string $id): bool
    {
        $expensetype = Expensetype::withTrashed()->findOrFail($id);

        $result = $expensetype->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get expensetype statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Expensetype::query()->count(),
            'deleted' => Expensetype::onlyTrashed()->count(),
            'created_today' => Expensetype::query()->whereDate('created_at', today())->count(),
            'created_this_week' => Expensetype::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => Expensetype::query()->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
