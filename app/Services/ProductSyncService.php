<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Gender;
use App\Models\Product;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

final readonly class ProductSyncService
{
    /**
     * Synchronize products, categories, and genders from external database.
     *
     * @return array<string, int> Statistics about synchronization
     */
    public function sync(): array
    {
        try {
            $stats = [
                'categories_created' => 0,
                'categories_skipped' => 0,
                'genders_created' => 0,
                'genders_skipped' => 0,
                'products_created' => 0,
                'products_updated' => 0,
                'products_skipped' => 0,
            ];

            // Get products from external database with joins
            $externalProducts = $this->getProductsFromExternal();

            // First pass: sync categories and genders
            $categoryMap = [];
            $genderMap = [];

            foreach ($externalProducts as $externalProduct) {
                // Sync main category
                if ($externalProduct->main_category_id && $externalProduct->main_category_name && ! isset($categoryMap[$externalProduct->main_category_id])) {
                    $category = Category::query()->firstOrNew([
                        'category_id' => $externalProduct->main_category_id,
                    ]);
                    $wasNew = ! $category->exists;
                    $category->category_name = $externalProduct->main_category_name;
                    $category->save();
                    if ($wasNew) {
                        $stats['categories_created']++;
                    } else {
                        $stats['categories_skipped']++;
                    }
                    $categoryMap[$externalProduct->main_category_id] = $category->id;
                }

                // Sync marketing category
                if ($externalProduct->marketing_category_id && $externalProduct->marketing_category_name && ! isset($categoryMap[$externalProduct->marketing_category_id])) {
                    $category = Category::query()->firstOrNew([
                        'category_id' => $externalProduct->marketing_category_id,
                    ]);
                    $wasNew = ! $category->exists;
                    $category->category_name = $externalProduct->marketing_category_name;
                    $category->save();
                    if ($wasNew) {
                        $stats['categories_created']++;
                    } else {
                        $stats['categories_skipped']++;
                    }
                    $categoryMap[$externalProduct->marketing_category_id] = $category->id;
                }

                // Sync gender
                if ($externalProduct->gender_id && $externalProduct->gender_name && ! isset($genderMap[$externalProduct->gender_id])) {
                    $gender = Gender::query()->firstOrNew([
                        'gender_id' => $externalProduct->gender_id,
                    ]);
                    $wasNew = ! $gender->exists;
                    $gender->gender_name = $externalProduct->gender_name;
                    $gender->save();
                    if ($wasNew) {
                        $stats['genders_created']++;
                    } else {
                        $stats['genders_skipped']++;
                    }
                    $genderMap[$externalProduct->gender_id] = $gender->id;
                }
            }

            // Second pass: sync products
            foreach ($externalProducts as $externalProduct) {
                // Find or create brand by name
                $brandId = null;
                if ($externalProduct->Brand) {
                    $brandName = mb_trim($externalProduct->Brand);
                    if ($brandName !== '') {
                        $brand = Brand::query()->firstOrNew(['name' => $brandName]);
                        if (! $brand->exists) {
                            $brand->save();
                        }
                        $brandId = $brand->id;
                    }
                }

                // Find or create product
                $product = Product::query()->firstOrNew([
                    'ProductID' => $externalProduct->ProductID,
                ]);

                $isNew = ! $product->exists;

                $product->Product = $externalProduct->Product;
                $product->newSystem = (bool) $externalProduct->newSystem;
                $product->Visible = (bool) $externalProduct->Visible;
                $product->flyer = (bool) $externalProduct->flyer;
                $product->brand_id = $brandId;
                $product->main_category_id = $categoryMap[$externalProduct->main_category_id] ?? null;
                $product->marketing_category_id = $categoryMap[$externalProduct->marketing_category_id] ?? null;
                $product->gender_id = $genderMap[$externalProduct->gender_id] ?? null;

                $product->save();

                if ($isNew) {
                    $stats['products_created']++;
                } else {
                    $stats['products_updated']++;
                }
            }

            Log::info('Products synchronization completed', $stats);

            return $stats;
        } catch (Exception $e) {
            Log::error('Products synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get products from external database with joins.
     *
     * @return Collection<int, stdClass>
     */
    private function getProductsFromExternal(): Collection
    {
        return DB::connection('mysql_external')
            ->table('product as p')
            ->leftJoin('category as mc', 'p.main_category_id', '=', 'mc.category_id')
            ->leftJoin('category as mkt', 'p.marketing_category_id', '=', 'mkt.category_id')
            ->leftJoin('gender as g', 'p.gender_id', '=', 'g.gender_id')
            ->select([
                'p.ProductID',
                'p.Product',
                'p.newSystem',
                'p.Visible',
                'p.flyer',
                'p.Brand',
                'mc.category_name as main_category_name',
                'mc.category_id as main_category_id',
                'mkt.category_name as marketing_category_name',
                'mkt.category_id as marketing_category_id',
                'g.gender_name as gender_name',
                'g.gender_id as gender_id',
            ])
            ->get();
    }
}
