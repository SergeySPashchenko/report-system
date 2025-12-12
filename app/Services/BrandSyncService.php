<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\SecureSeller\Product;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

final readonly class BrandSyncService
{
    /**
     * Synchronize brands from external database.
     *
     * @return array<string, int> Statistics about synchronization
     */
    public function sync(): array
    {
        try {
            // Get brands from external database
            $externalBrands = $this->getBrandsFromExternal();

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($externalBrands as $externalBrand) {
                // Get brand name, handle null/empty values
                $brandValue = $externalBrand->Brand ?? null;
                $brandName = $brandValue !== null && is_string($brandValue) ? mb_trim($brandValue) : '';

                // If brand is empty or null, use "without brand"
                if ($brandName === '') {
                    $brandName = 'without brand';
                }

                // Find or create brand in local database
                $brand = Brand::query()->firstOrNew(['name' => $brandName]);

                if ($brand->exists) {
                    // Brand already exists, skip
                    $skipped++;
                } else {
                    // Create new brand
                    $brand->save();
                    $created++;

                    $productCount = $externalBrand->ProductCount ?? 0;

                    Log::info('Brand synced from external database', [
                        'brand_id' => $brand->id,
                        'brand_name' => $brandName,
                        'product_count' => $productCount,
                    ]);
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total_external' => $externalBrands->count(),
            ];
        } catch (Exception $e) {
            Log::error('Brand synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get statistics about external brands.
     *
     * @return array<string, mixed>
     */
    public function getExternalStats(): array
    {
        try {
            $totalProducts = Product::query()->count();
            $brandsWithProducts = Product::query()
                ->whereNotNull('Brand')
                ->where('Brand', '!=', '')
                ->distinct('Brand')
                ->count('Brand');
            $productsWithoutBrand = Product::query()
                ->where(function ($query): void {
                    $query->whereNull('Brand')
                        ->orWhere('Brand', '');
                })
                ->count();

            return [
                'total_products' => $totalProducts,
                'brands_count' => $brandsWithProducts,
                'products_without_brand' => $productsWithoutBrand,
            ];
        } catch (Exception $e) {
            Log::error('Failed to get external brand statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'total_products' => 0,
                'brands_count' => 0,
                'products_without_brand' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get brands from external database grouped by Brand name.
     *
     * @return Collection<int, Product> Collection of Product models with Brand and ProductCount attributes
     */
    private function getBrandsFromExternal(): Collection
    {
        return Product::query()
            ->selectRaw('Brand, COUNT(ProductID) as ProductCount')
            ->groupBy('Brand')
            ->orderBy('ProductCount', 'desc')
            ->get();
    }
}
