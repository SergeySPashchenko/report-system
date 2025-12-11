<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class UserQuery
{
    /**
     * @var Builder<User>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = User::query();
    }

    /**
     * Search users by name, email, or username.
     */
    public function search(?string $search): self
    {
        if ($search !== null && $search !== '') {
            $this->query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return $this;
    }

    /**
     * Sort users by column and direction.
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
     * Filter verified users.
     */
    public function verified(): self
    {
        $this->query->whereNotNull('email_verified_at');

        return $this;
    }

    /**
     * Filter active users (verified and not deleted).
     *
     * @return Builder<User>
     */
    public function activeUsers(): Builder
    {
        return $this->query
            ->whereNotNull('email_verified_at')
            ->whereNull('deleted_at');
    }

    /**
     * Get recent users from last N days.
     */
    public function recentUsers(int $days = 7): self
    {
        $this->query->where('created_at', '>=', now()->subDays($days));

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
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, User> $paginator */
        $paginator = $this->query->paginate($perPage);

        return $paginator;
    }

    /**
     * Get all results.
     *
     * @return Collection<int, User>
     */
    public function get(): Collection
    {
        /** @var Collection<int, User> $collection */
        $collection = $this->query->get();

        return $collection;
    }

    /**
     * Find user by username.
     */
    public function findByUsername(string $username): ?User
    {
        /** @var User|null $user */
        $user = $this->query->where('username', $username)->first();

        return $user;
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->query->where('email', $email)->first();

        return $user;
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder<User>
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
        $this->query = User::query();

        return $this;
    }
}
