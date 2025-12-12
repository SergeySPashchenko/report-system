<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Gender;
use App\Models\Product;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

final readonly class ExpenseSyncService
{
    /**
     * Synchronize expenses from external database for a specific date.
     *
     * @return array<string, int> Statistics about synchronization
     */
    public function syncForDate(string $date): array
    {
        try {
            $stats = [
                'brands_created' => 0,
                'brands_skipped' => 0,
                'expensetypes_created' => 0,
                'expensetypes_skipped' => 0,
                'categories_created' => 0,
                'categories_skipped' => 0,
                'genders_created' => 0,
                'genders_skipped' => 0,
                'products_created' => 0,
                'products_updated' => 0,
                'expenses_created' => 0,
                'expenses_skipped' => 0,
            ];

            // Get expenses from external database with joins
            $externalExpenses = $this->getExpensesFromExternal($date);

            // Maps for tracking created entities
            $brandMap = [];
            $expensetypeMap = [];
            $categoryMap = [];
            $genderMap = [];
            $productMap = [];

            // First pass: sync brands, expensetypes, categories, genders, and products
            foreach ($externalExpenses as $externalExpense) {
                // Sync brand if exists and not empty
                if ($externalExpense->Brand) {
                    $brandName = mb_trim($externalExpense->Brand);
                    if ($brandName !== '' && ! isset($brandMap[$brandName])) {
                        $brand = Brand::query()->firstOrNew(['name' => $brandName]);
                        $wasNew = ! $brand->exists;
                        if ($wasNew) {
                            $brand->save();
                            $stats['brands_created']++;
                        } else {
                            $stats['brands_skipped']++;
                        }
                        $brandMap[$brandName] = $brand->id;
                    }
                }

                // Sync expensetype
                if ($externalExpense->ExpenseID && $externalExpense->Name && ! isset($expensetypeMap[$externalExpense->ExpenseID])) {
                    $expensetype = Expensetype::query()->firstOrNew([
                        'ExpenseTypeID' => $externalExpense->ExpenseID,
                    ]);
                    $wasNew = ! $expensetype->exists;
                    $expensetype->Name = $externalExpense->Name;
                    $expensetype->save();
                    if ($wasNew) {
                        $stats['expensetypes_created']++;
                    } else {
                        $stats['expensetypes_skipped']++;
                    }
                    $expensetypeMap[$externalExpense->ExpenseID] = $expensetype->ExpenseTypeID;
                }

                // Sync main category if product exists
                if ($externalExpense->ProductID && $externalExpense->main_category_id && $externalExpense->category_name && ! isset($categoryMap[$externalExpense->main_category_id])) {
                    $category = Category::query()->firstOrNew([
                        'category_id' => $externalExpense->main_category_id,
                    ]);
                    $wasNew = ! $category->exists;
                    $category->category_name = $externalExpense->category_name;
                    $category->save();
                    if ($wasNew) {
                        $stats['categories_created']++;
                    } else {
                        $stats['categories_skipped']++;
                    }
                    $categoryMap[$externalExpense->main_category_id] = $category->id;
                }

                // Sync marketing category if exists
                if ($externalExpense->marketing_category_id && $externalExpense->marketing_category_name && ! isset($categoryMap[$externalExpense->marketing_category_id])) {
                    $category = Category::query()->firstOrNew([
                        'category_id' => $externalExpense->marketing_category_id,
                    ]);
                    $wasNew = ! $category->exists;
                    $category->category_name = $externalExpense->marketing_category_name;
                    $category->save();
                    if ($wasNew) {
                        $stats['categories_created']++;
                    } else {
                        $stats['categories_skipped']++;
                    }
                    $categoryMap[$externalExpense->marketing_category_id] = $category->id;
                }

                // Sync gender if product exists
                if ($externalExpense->ProductID && $externalExpense->gender_id && $externalExpense->gender_name && ! isset($genderMap[$externalExpense->gender_id])) {
                    $gender = Gender::query()->firstOrNew([
                        'gender_id' => $externalExpense->gender_id,
                    ]);
                    $wasNew = ! $gender->exists;
                    $gender->gender_name = $externalExpense->gender_name;
                    $gender->save();
                    if ($wasNew) {
                        $stats['genders_created']++;
                    } else {
                        $stats['genders_skipped']++;
                    }
                    $genderMap[$externalExpense->gender_id] = $gender->id;
                }

                // Sync product if exists
                if ($externalExpense->ProductID && ! isset($productMap[$externalExpense->ProductID])) {
                    $product = Product::query()->firstOrNew([
                        'ProductID' => $externalExpense->ProductID,
                    ]);
                    $wasNew = ! $product->exists;

                    $product->Product = $externalExpense->Product ?? '';
                    $product->newSystem = (bool) ($externalExpense->newSystem ?? false);
                    $product->Visible = (bool) ($externalExpense->Visible ?? true);
                    $product->flyer = (bool) ($externalExpense->flyer ?? false);

                    // Set brand ID from map (brands are synced in first pass)
                    if ($externalExpense->Brand) {
                        $brandName = mb_trim($externalExpense->Brand);
                        if ($brandName !== '' && isset($brandMap[$brandName])) {
                            $product->brand_id = $brandMap[$brandName];
                        }
                    }

                    // Set category and gender IDs
                    $product->main_category_id = $categoryMap[$externalExpense->main_category_id] ?? null;
                    $product->marketing_category_id = $categoryMap[$externalExpense->marketing_category_id] ?? null;
                    $product->gender_id = $genderMap[$externalExpense->gender_id] ?? null;

                    $product->save();

                    if ($wasNew) {
                        $stats['products_created']++;
                    } else {
                        $stats['products_updated']++;
                    }

                    $productMap[$externalExpense->ProductID] = $product->ProductID;
                }
            }

            // Second pass: sync expenses
            foreach ($externalExpenses as $externalExpense) {
                // Sync expense
                $expense = Expense::query()->firstOrNew([
                    'external_id' => $externalExpense->id,
                ]);

                $wasNew = ! $expense->exists;
                $expense->ProductID = $externalExpense->ProductID;
                $expense->ExpenseID = $externalExpense->ExpenseID;
                $expense->ExpenseDate = $externalExpense->ExpenseDate;
                $expense->Expense = $externalExpense->Expense;

                $expense->save();

                if ($wasNew) {
                    $stats['expenses_created']++;
                } else {
                    $stats['expenses_skipped']++;
                }
            }

            Log::info('Expenses synchronization completed', [
                'date' => $date,
                ...$stats,
            ]);

            return $stats;
        } catch (Exception $e) {
            Log::error('Expenses synchronization failed', [
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get expenses from external database with joins.
     *
     * @return Collection<int, stdClass>
     */
    private function getExpensesFromExternal(string $date): Collection
    {
        return DB::connection('mysql_external')
            ->table('expenses as e')
            ->leftJoin('product as p', 'p.ProductID', '=', 'e.ProductID')
            ->leftJoin('category as mc', 'p.main_category_id', '=', 'mc.category_id')
            ->leftJoin('category as mkt', 'p.marketing_category_id', '=', 'mkt.category_id')
            ->leftJoin('gender as g', 'p.gender_id', '=', 'g.gender_id')
            ->leftJoin('expensetype as et', 'et.ExpenseID', '=', 'e.ExpenseID')
            ->where('e.ExpenseDate', $date)
            ->select([
                'e.id',
                'e.ProductID',
                'e.ExpenseID',
                'e.ExpenseDate',
                'e.Expense',
                'et.Name',
                'p.Product',
                'p.newSystem',
                'p.Visible',
                'mc.category_name',
                'mc.category_id as main_category_id',
                'mkt.category_name as marketing_category_name',
                'mkt.category_id as marketing_category_id',
                'g.gender_name',
                'g.gender_id',
                'p.flyer',
                'p.Brand',
            ])
            ->orderBy('e.id')
            ->get();
    }
}
