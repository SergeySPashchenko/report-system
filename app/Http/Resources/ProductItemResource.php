<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ProductItem $resource
 */
final class ProductItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ItemID' => $this->resource->ItemID,
            'ProductID' => $this->resource->ProductID,
            'ProductName' => $this->resource->ProductName,
            'slug' => $this->resource->slug,
            'SKU' => $this->resource->SKU,
            'Quantity' => $this->resource->Quantity,
            'upSell' => $this->resource->upSell,
            'extraProduct' => $this->resource->extraProduct,
            'offerProducts' => $this->resource->offerProducts,
            'active' => $this->resource->active,
            'deleted' => $this->resource->deleted,
            'product' => $this->whenLoaded('product', function (): ?ProductResource {
                /** @var Product|null $product */
                $product = $this->resource->product;

                return $product ? new ProductResource($product) : null;
            }),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
