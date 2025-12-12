<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    private Product $product;

    private Expense $expense;

    private Expensetype $expensetype;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['category_name' => 'Test Category']);
        $this->product = Product::factory()->create([
            'ProductID' => 12345,
            'main_category_id' => $this->category->id,
        ]);
        $this->expensetype = Expensetype::factory()->create(['ExpenseTypeID' => 1]);

        $this->expense = Expense::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
            'ExpenseDate' => '2022-07-02',
            'Expense' => 100.50,
        ]);
    }

    public function test_can_list_categories(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'category_name', 'slug', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_categories(): void
    {
        Category::factory()->create(['category_name' => 'Tech Category']);
        Category::factory()->create(['category_name' => 'Fashion Category']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories?search=Tech');

        $response->assertStatus(200);
    }

    public function test_can_show_category(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/categories/{$this->category->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->category->id)
            ->assertJsonPath('data.category_name', $this->category->category_name);
    }

    public function test_can_create_category(): void
    {
        $categoryData = [
            'category_name' => 'New Category',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonPath('data.category_name', 'New Category');

        $this->assertDatabaseHas('categories', [
            'category_name' => 'New Category',
        ]);
    }

    public function test_can_update_category(): void
    {
        $updateData = [
            'category_name' => 'Updated Category Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/categories/{$this->category->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.category_name', 'Updated Category Name');

        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'category_name' => 'Updated Category Name',
        ]);
    }

    public function test_can_delete_category(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$this->category->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('categories', [
            'id' => $this->category->id,
        ]);
    }

    public function test_validation_fails_for_missing_category_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_name']);
    }

    public function test_guest_cannot_access_categories_endpoint(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(401);
    }

    public function test_can_get_category_statistics(): void
    {
        Category::factory()->count(5)->create();
        $deleted = Category::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'created_today',
                'created_this_week',
                'created_this_month',
            ]);
    }

    public function test_can_list_products_for_category(): void
    {
        Product::factory()->count(3)->create(['main_category_id' => $this->category->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/categories/{$this->category->slug}/products");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'Product', 'slug'],
                ],
            ]);
    }

    public function test_can_create_product_for_category_without_main_category_id(): void
    {
        $brand = Brand::factory()->create();
        $gender = Gender::factory()->create();

        $productData = [
            'ProductID' => 11111,
            'Product' => 'Category Product',
            'brand_id' => $brand->id,
            'gender_id' => $gender->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/categories/{$this->category->slug}/products", $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Product', 'Category Product')
            ->assertJsonPath('data.main_category_id', $this->category->id);

        $this->assertDatabaseHas('products', [
            'ProductID' => 11111,
            'Product' => 'Category Product',
            'main_category_id' => $this->category->id,
        ]);
    }

    public function test_returns_404_if_product_not_belongs_to_category(): void
    {
        $otherCategory = Category::factory()->create();
        $otherProduct = Product::factory()->create(['main_category_id' => $otherCategory->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/categories/{$this->category->slug}/products/{$otherProduct->slug}");

        $response->assertStatus(404);
    }

    public function test_can_list_expenses_for_category(): void
    {
        Expense::factory()->count(3)->create([
            'ProductID' => $this->product->ProductID,
            'ExpenseID' => $this->expensetype->ExpenseTypeID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/categories/{$this->category->slug}/expenses");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'ExpenseID', 'ExpenseDate', 'Expense'],
                ],
            ]);
    }
}
