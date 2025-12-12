<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Gender;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
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
            'ProductID' => $this->ProductID,
            'Product' => $this->Product,
            'slug' => $this->slug,
            'newSystem' => $this->newSystem,
            'Visible' => $this->Visible,
            'flyer' => $this->flyer,
            'brand_id' => $this->brand_id,
            'main_category_id' => $this->main_category_id,
            'marketing_category_id' => $this->marketing_category_id,
            'gender_id' => $this->gender_id,
            'created_at' => $createdAt->toIso8601String(),
            'updated_at' => $updatedAt->toIso8601String(),
            'deleted_at' => $deletedAt?->toIso8601String(),

            // Relationships
            'brand' => $this->whenLoaded('brand', function (): BrandResource {
                /** @var Product $product */
                $product = $this->resource;
                /** @var Brand $brand */
                $brand = $product->brand;

                return new BrandResource($brand);
            }),
            'main_category' => $this->whenLoaded('main_category', function (): CategoryResource {
                /** @var Product $product */
                $product = $this->resource;
                /** @var Category $mainCategory */
                $mainCategory = $product->main_category;

                return new CategoryResource($mainCategory);
            }),
            'marketing_category' => $this->whenLoaded('marketing_category', function (): CategoryResource {
                /** @var Product $product */
                $product = $this->resource;
                /** @var Category $marketingCategory */
                $marketingCategory = $product->marketing_category;

                return new CategoryResource($marketingCategory);
            }),
            'gender' => $this->whenLoaded('gender', function (): GenderResource {
                /** @var Product $product */
                $product = $this->resource;
                /** @var Gender $gender */
                $gender = $product->gender;

                return new GenderResource($gender);
            }),

            // Links
            'links' => [
                'self' => url("/api/v1/products/{$this->slug}"),
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
