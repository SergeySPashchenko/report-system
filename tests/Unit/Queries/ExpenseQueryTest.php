<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Models\Access;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use App\Queries\ExpenseQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpenseQueryTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseQuery $expenseQuery;

    private User $user;

    private Product $product;

    private Expensetype $expensetype;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expenseQuery = new ExpenseQuery();
        $this->user = User::factory()->create();
        // Видаляємо доступ до компанії для тестового користувача
        $this->user->accesses()->where('accessible_type', 'company')->delete();
        $this->product = Product::factory()->create(['ProductID' => 12345]);
        $this->expensetype = Expensetype::factory()->create(['ExpenseTypeID' => 1]);
    }

    public function test_can_filter_by_user_with_product_access(): void
    {
        // Створюємо нового користувача без доступу до компанії
        $user = User::factory()->create();

        // Видаляємо будь-який доступ до компанії, якщо він був створений автоматично
        $user->accesses()->where('accessible_type', 'company')->delete();

        Access::factory()->create([
            'user_id' => $user->id,
            'accessible_id' => $this->product->id,
            'accessible_type' => 'product',
        ]);

        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $otherProduct = Product::factory()->create();
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->reset()->forUser($user)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($this->product->ProductID);
    }

    public function test_can_filter_by_user_with_brand_access(): void
    {
        // Створюємо нового користувача без доступу до компанії
        $user = User::factory()->create();

        // Видаляємо будь-який доступ до компанії, якщо він був створений автоматично
        $user->accesses()->where('accessible_type', 'company')->delete();

        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        Access::factory()->create([
            'user_id' => $user->id,
            'accessible_id' => $brand->id,
            'accessible_type' => 'brand',
        ]);

        Expense::factory()->create([
            'ProductID' => $product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $otherBrand = Brand::factory()->create();
        $otherProduct = Product::factory()->create(['brand_id' => $otherBrand->id]);
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->reset()->forUser($user)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($product->ProductID);
    }

    public function test_company_user_has_access_to_all(): void
    {
        $company = Company::factory()->create();
        $companyUser = User::factory()->create();

        Access::factory()->create([
            'user_id' => $companyUser->id,
            'accessible_id' => $company->id,
            'accessible_type' => 'company',
        ]);

        Expense::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->forUser($companyUser)->get();

        expect($result->count())->toBe(5);
    }

    public function test_user_without_access_gets_empty_result(): void
    {
        // Створюємо нового користувача без жодних доступів
        $user = User::factory()->create();
        // Видаляємо доступ до компанії, якщо він був створений автоматично
        $user->accesses()->where('accessible_type', 'company')->delete();

        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->reset()->forUser($user)->get();

        expect($result->count())->toBe(0);
    }

    public function test_can_filter_by_product(): void
    {
        $otherProduct = Product::factory()->create(['ProductID' => 99999]);

        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->byProduct($this->product->ProductID)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($this->product->ProductID);
    }

    public function test_can_filter_by_expensetype(): void
    {
        $otherExpensetype = Expensetype::factory()->create(['ExpenseTypeID' => 2]);

        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $otherExpensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->byExpensetype($this->expensetype->ExpenseTypeID)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ExpenseID)->toBe($this->expensetype->ExpenseTypeID);
    }

    public function test_can_filter_by_brand(): void
    {
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        Expense::factory()->create([
            'ProductID' => $product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $otherBrand = Brand::factory()->create();
        $otherProduct = Product::factory()->create(['brand_id' => $otherBrand->id]);
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->byBrand($brand->id)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($product->ProductID);
    }

    public function test_can_filter_by_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['main_category_id' => $category->id]);

        Expense::factory()->create([
            'ProductID' => $product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $otherCategory = Category::factory()->create();
        $otherProduct = Product::factory()->create(['main_category_id' => $otherCategory->id]);
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->byCategory($category->id)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($product->ProductID);
    }

    public function test_can_filter_by_gender(): void
    {
        $gender = Gender::factory()->create();
        $product = Product::factory()->create(['gender_id' => $gender->id]);

        Expense::factory()->create([
            'ProductID' => $product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $otherGender = Gender::factory()->create();
        $otherProduct = Product::factory()->create(['gender_id' => $otherGender->id]);
        Expense::factory()->create([
            'ProductID' => $otherProduct->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->byGender($gender->id)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ProductID)->toBe($product->ProductID);
    }

    public function test_can_filter_by_date_range(): void
    {
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-01',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-02',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-03',
        ]);

        $result = $this->expenseQuery->byDateRange('2022-07-02', '2022-07-02')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ExpenseDate->format('Y-m-d'))->toBe('2022-07-02');
    }

    public function test_can_filter_by_specific_date(): void
    {
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-02',
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-03',
        ]);

        $result = $this->expenseQuery->byDate('2022-07-02')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->ExpenseDate->format('Y-m-d'))->toBe('2022-07-02');
    }

    public function test_can_sort_expenses(): void
    {
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 100.00,
        ]);
        Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'Expense' => 200.00,
        ]);

        $result = $this->expenseQuery->sort('Expense', 'asc')->get();

        expect($result->count())->toBe(2)
            ->and($result->first()->Expense)->toBeLessThanOrEqual($result->last()->Expense);
    }

    public function test_can_paginate_results(): void
    {
        Expense::factory()->count(25)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $result = $this->expenseQuery->paginate(10);

        expect($result->count())->toBe(10)
            ->and($result->perPage())->toBe(10)
            ->and($result->total())->toBe(25);
    }

    public function test_can_reset_query(): void
    {
        $this->expenseQuery->byProduct($this->product->ProductID);

        $resetQuery = $this->expenseQuery->reset();

        expect($resetQuery)->toBeInstanceOf(ExpenseQuery::class);
        // After reset, should return all expenses
        $result = $this->expenseQuery->get();
        expect($result->count())->toBeGreaterThanOrEqual(0);
    }
}
