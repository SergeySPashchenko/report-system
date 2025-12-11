<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Carbon|null $emailVerifiedAt */
        $emailVerifiedAt = $this->email_verified_at;
        /** @var Carbon $createdAt */
        $createdAt = $this->created_at;
        /** @var Carbon $updatedAt */
        $updatedAt = $this->updated_at;
        /** @var Carbon|null $deletedAt */
        $deletedAt = $this->deleted_at;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $emailVerifiedAt?->toIso8601String(),
            'created_at' => $createdAt->toIso8601String(),
            'updated_at' => $updatedAt->toIso8601String(),
            'deleted_at' => $deletedAt?->toIso8601String(),

            // Додаткові поля (опціонально)
            'links' => [
                'self' => url("/api/v1/users/{$this->username}"),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0.0',
            ],
        ];
    }
}
