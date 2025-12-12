<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Product;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expense
 */
final class ExpenseResource extends JsonResource
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
        /** @var DateTimeInterface|string $expenseDate */
        $expenseDate = $this->ExpenseDate;

        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'ProductID' => $this->ProductID,
            'ExpenseID' => $this->ExpenseID,
            'ExpenseDate' => $expenseDate instanceof DateTimeInterface ? $expenseDate->format('Y-m-d') : $expenseDate,
            'Expense' => (float) $this->Expense,
            'created_at' => $createdAt->toIso8601String(),
            'updated_at' => $updatedAt->toIso8601String(),
            'deleted_at' => $deletedAt?->toIso8601String(),

            // Relationships
            'product' => $this->whenLoaded('product', function (): ProductResource {
                /** @var Expense $expense */
                $expense = $this->resource;
                /** @var Product|null $product */
                $product = $expense->product;

                return $product ? new ProductResource($product) : null;
            }),
            'expensetype' => $this->whenLoaded('expensetype', function (): ExpensetypeResource {
                /** @var Expense $expense */
                $expense = $this->resource;
                /** @var Expensetype|null $expensetype */
                $expensetype = $expense->expensetype;

                return $expensetype ? new ExpensetypeResource($expensetype) : null;
            }),

            // Links
            'links' => [
                'self' => url("/api/v1/expenses/{$this->id}"),
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
