<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Queries\UserQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

final readonly class UserService
{
    public function __construct(
        private UserQuery $userQuery
    ) {}

    /**
     * Get paginated users with filters.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getPaginatedUsers(
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->userQuery
            ->reset()
            ->search($search)
            ->sort($sortBy, $sortDirection ?? 'asc')
            ->paginate($perPage);
    }

    /**
     * Find user by username.
     */
    public function findByUsername(string $username): ?User
    {
        return $this->userQuery->findByUsername($username);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->userQuery->findByEmail($email);
    }

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        /** @var array{name: string, email: string, password: string} $data */
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Update user.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['password']) && is_string($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        /** @var array<string, mixed> $updateData */
        $updateData = $data;
        $user->update($updateData);

        $fresh = $user->fresh();

        assert($fresh instanceof User);

        return $fresh;
    }

    /**
     * Delete user (soft delete).
     */
    public function delete(User $user): bool
    {
        $result = $user->delete();

        return $result !== null && (bool) $result;
    }

    /**
     * Restore soft deleted user.
     */
    public function restore(string $id): User
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return $user;
    }

    /**
     * Permanently delete user.
     */
    public function forceDelete(string $id): bool
    {
        $user = User::withTrashed()->findOrFail($id);

        $result = $user->forceDelete();

        return $result !== null && (bool) $result;
    }

    /**
     * Get active users count.
     */
    public function getActiveUsersCount(): int
    {
        return $this->userQuery->reset()->activeUsers()->count();
    }

    /**
     * Get recently registered users.
     *
     * @return array<int, User>
     */
    public function getRecentUsers(int $limit = 10): array
    {
        return $this->userQuery
            ->reset()
            ->recentUsers()
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Check if user is active.
     */
    public function isActive(User $user): bool
    {
        return $user->deleted_at === null && $user->email_verified_at !== null;
    }

    /**
     * Activate user.
     */
    public function activate(User $user): User
    {
        if ($user->email_verified_at === null) {
            /** @phpstan-ignore-next-line */
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): User
    {
        $user->email_verified_at = null;
        $user->save();

        // Revoke all tokens
        $user->tokens()->delete();

        return $user;
    }

    /**
     * Get user statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->whereNotNull('email_verified_at')->count(),
            'inactive' => User::query()->whereNull('email_verified_at')->count(),
            'deleted' => User::onlyTrashed()->count(),
            'registered_today' => User::query()->whereDate('created_at', today())->count(),
            'registered_this_week' => User::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'registered_this_month' => User::query()->whereMonth('created_at', now()->month)->count(),
        ];
    }
}
