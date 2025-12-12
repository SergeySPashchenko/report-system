<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Gender;
use App\Queries\GenderQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GenderService
{
    public function __construct(
        private GenderQuery $genderQuery
    ) {}

    /**
     * Get paginated genders with filters.
     *
     * @return LengthAwarePaginator<int, Gender>
     */
    public function getPaginatedGenders(
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->genderQuery
            ->reset()
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find gender by slug.
     */
    public function findBySlug(string $slug): ?Gender
    {
        return $this->genderQuery->findBySlug($slug);
    }

    /**
     * Create a new gender.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Gender
    {
        /** @var array{gender_name: string, gender_id?: int} $data */
        return Gender::query()->create([
            'gender_name' => $data['gender_name'],
            'gender_id' => $data['gender_id'] ?? null,
        ]);
    }

    /**
     * Update gender.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Gender $gender, array $data): Gender
    {
        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $gender->update($updateData);

        $fresh = $gender->fresh();

        assert($fresh instanceof Gender);

        return $fresh;
    }

    /**
     * Delete gender (soft delete).
     */
    public function delete(Gender $gender): bool
    {
        $result = $gender->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted gender.
     */
    public function restore(string $id): Gender
    {
        $gender = Gender::withTrashed()->findOrFail($id);
        $gender->restore();

        return $gender;
    }

    /**
     * Permanently delete gender.
     */
    public function forceDelete(string $id): bool
    {
        $gender = Gender::withTrashed()->findOrFail($id);

        $result = $gender->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get gender statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Gender::query()->count(),
            'deleted' => Gender::onlyTrashed()->count(),
            'created_today' => Gender::query()->whereDate('created_at', today())->count(),
            'created_this_week' => Gender::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'created_this_month' => Gender::query()->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
