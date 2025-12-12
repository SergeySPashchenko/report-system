<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

final readonly class ProductItemSyncService
{
    /**
     * Synchronize product items from external database.
     *
     * @return array<string, int> Statistics about synchronization
     */
    public function sync(): array
    {
        try {
            $stats = [
                'products_created' => 0,
                'products_skipped' => 0,
                'product_items_created' => 0,
                'product_items_updated' => 0,
                'product_items_skipped' => 0,
            ];

            // Get product items from external database
            $externalProductItems = $this->getProductItemsFromExternal();

            // Map for tracking products
            $productMap = [];

            // First pass: ensure products exist
            foreach ($externalProductItems as $externalItem) {
                if ($externalItem->ProductID && ! isset($productMap[$externalItem->ProductID])) {
                    $product = Product::query()->where('ProductID', $externalItem->ProductID)->first();
                    if (! $product) {
                        // Create a minimal product if it doesn't exist
                        $product = Product::query()->create([
                            'ProductID' => $externalItem->ProductID,
                            'Product' => $externalItem->ProductName ?? 'Unknown Product',
                        ]);
                        $stats['products_created']++;
                    } else {
                        $stats['products_skipped']++;
                    }
                    $productMap[$externalItem->ProductID] = $product->ProductID;
                }
            }

            // Second pass: sync product items
            foreach ($externalProductItems as $externalItem) {
                if (! $externalItem->ItemID) {
                    continue;
                }

                // Check if product item exists (including soft deleted)
                $productItem = ProductItem::withTrashed()->where('ItemID', $externalItem->ItemID)->first();

                if ($productItem) {
                    // Update existing product item
                    $productItem->update([
                        'ProductID' => $externalItem->ProductID ?? null,
                        'ProductName' => $externalItem->ProductName,
                        'SKU' => $externalItem->SKU ?? '',
                        'Quantity' => $externalItem->Quantity ?? 0,
                        'upSell' => (bool) ($externalItem->upSell ?? false),
                        'extraProduct' => (bool) ($externalItem->extraProduct ?? false),
                        'offerProducts' => $externalItem->offerProducts ?? null,
                        'active' => (bool) ($externalItem->active ?? true),
                        'deleted' => (bool) ($externalItem->deleted ?? false),
                    ]);
                    $stats['product_items_updated']++;
                } else {
                    // Create new product item
                    $productItem = ProductItem::query()->create([
                        'ItemID' => $externalItem->ItemID,
                        'ProductID' => $externalItem->ProductID ?? null,
                        'ProductName' => $externalItem->ProductName,
                        'SKU' => $externalItem->SKU ?? '',
                        'Quantity' => $externalItem->Quantity ?? 0,
                        'upSell' => (bool) ($externalItem->upSell ?? false),
                        'extraProduct' => (bool) ($externalItem->extraProduct ?? false),
                        'offerProducts' => $externalItem->offerProducts ?? null,
                        'active' => (bool) ($externalItem->active ?? true),
                        'deleted' => (bool) ($externalItem->deleted ?? false),
                    ]);
                    $wasNew = true;
                    $stats['product_items_created']++;
                }

                // Handle soft delete if deleted flag is set
                // Refresh the model to ensure we have the latest state
                $productItem->refresh();
                if ($productItem->deleted && ! $productItem->trashed()) {
                    $productItem->delete();
                } elseif (! $productItem->deleted && $productItem->trashed()) {
                    $productItem->restore();
                }
            }

            Log::info('Product items synchronization completed', [
                'stats' => $stats,
            ]);

            return $stats;
        } catch (Exception $e) {
            Log::error('Product items synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get product items from external database.
     *
     * @return Collection<int, stdClass>
     */
    private function getProductItemsFromExternal(): Collection
    {
        return DB::connection('mysql_external')
            ->table('ProductItem')
            ->select([
                'ItemID',
                'ProductID',
                'ProductName',
                'SKU',
                'Quantity',
                'upSell',
                'extraProduct',
                'offerProducts',
                'deleted',
                'active',
            ])
            ->orderBy('ItemID')
            ->get();
    }
}
