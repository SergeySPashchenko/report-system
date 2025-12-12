<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Gender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Gender
 */
final class GenderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Carbon $createdAt */
        $createdAt = $this->created_at;
        /** @var Carbon $updatedAt */
        $updatedAt = $this->updated_at;
        /** @var Carbon|null $deletedAt */
        $deletedAt = $this->deleted_at;

        return [
            'id' => $this->id,
            'gender_id' => $this->gender_id,
            'gender_name' => $this->gender_name,
            'slug' => $this->slug,
            'created_at' => $createdAt->toIso8601String(),
            'updated_at' => $updatedAt->toIso8601String(),
            'deleted_at' => $deletedAt?->toIso8601String(),

            // Links
            'links' => [
                'self' => url("/api/v1/genders/{$this->slug}"),
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
