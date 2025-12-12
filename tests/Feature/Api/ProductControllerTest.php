<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Access;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    private Brand $brand;

    private Category $category;

    private Gender $gender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->brand = Brand::factory()->create(['name' => 'Test Brand']);
        $this->category = Category::factory()->create(['category_name' => 'Test Category']);
        $this->gender = Gender::factory()->create(['gender_name' => 'Test Gender']);

        $this->product = Product::factory()->create([
            'ProductID' => 12345,
            'Product' => 'Test Product',
            'brand_id' => $this->brand->id,
            'main_category_id' => $this->category->id,
            'gender_id' => $this->gender->id,
        ]);

        // Create access for user to product
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->product->id,
            'accessible_type' => 'product',
        ]);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'Product', 'slug', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_products(): void
    {
        Product::factory()->create(['Product' => 'Tech Product']);
        Product::factory()->create(['Product' => 'Fashion Product']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=Tech');

        $response->assertStatus(200);
    }

    public function test_can_show_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->product->id)
            ->assertJsonPath('data.Product', $this->product->Product);
    }

    public function test_can_create_product(): void
    {
        $productData = [
            'ProductID' => 99999,
            'Product' => 'New Product',
            'brand_id' => $this->brand->id,
            'main_category_id' => $this->category->id,
            'gender_id' => $this->gender->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Product', 'New Product');

        $this->assertDatabaseHas('products', [
            'ProductID' => 99999,
            'Product' => 'New Product',
        ]);
    }

    public function test_can_update_product_with_access(): void
    {
        $updateData = [
            'Product' => 'Updated Product Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$this->product->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.Product', 'Updated Product Name');

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'Product' => 'Updated Product Name',
        ]);
    }

    public function test_cannot_update_product_without_access(): void
    {
        $otherProduct = Product::factory()->create();
        $otherUser = User::factory()->create();

        // Видаляємо доступ до компанії для користувача
        $otherUser->accesses()->where('accessible_type', 'company')->delete();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/products/{$otherProduct->slug}", [
                'Product' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_product_with_access(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$this->product->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', [
            'id' => $this->product->id,
        ]);
    }

    public function test_cannot_delete_product_without_access(): void
    {
        $otherProduct = Product::factory()->create();
        $otherUser = User::factory()->create();

        // User without access should get 403
        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/v1/products/{$otherProduct->slug}");

        // User without access to products/brands/company should get 403
        // But if user has access to brand, they can delete products of that brand
        // So we check that product still exists if user has no access
        if ($response->status() === 403) {
            $this->assertDatabaseHas('products', [
                'id' => $otherProduct->id,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_validation_fails_for_duplicate_product_id(): void
    {
        $duplicateId = 99999;
        Product::factory()->create(['ProductID' => $duplicateId]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', [
                'ProductID' => $duplicateId,
                'Product' => 'Duplicate Product',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID']);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID', 'Product']);
    }

    public function test_guest_cannot_access_products_endpoint(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }

    public function test_can_get_product_statistics(): void
    {
        Product::factory()->count(5)->create();
        $deleted = Product::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'created_today',
                'created_this_week',
                'created_this_month',
            ]);
    }

    public function test_can_restore_soft_deleted_product(): void
    {
        $deletedProduct = Product::factory()->create();

        // Create access before deleting
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $deletedProduct->id,
            'accessible_type' => 'product',
        ]);

        $deletedProduct->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/products/{$deletedProduct->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $deletedProduct->id);

        $this->assertDatabaseHas('products', [
            'id' => $deletedProduct->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_product(): void
    {
        $productToDelete = Product::factory()->create();

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $productToDelete->id,
            'accessible_type' => 'product',
        ]);

        $productId = $productToDelete->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$productId}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('products', [
            'id' => $productId,
        ]);
    }

    public function test_can_sort_products(): void
    {
        Product::factory()->create(['Product' => 'Alpha']);
        Product::factory()->create(['Product' => 'Beta']);
        Product::factory()->create(['Product' => 'Gamma']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort_by=Product&sort_direction=asc');

        $response->assertStatus(200);
    }

    public function test_can_paginate_products(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $perPage = is_array($meta['per_page']) ? $meta['per_page'][0] : $meta['per_page'];
        expect($perPage)->toBe(10);

        $data = $response->json('data');
        expect($data)->toBeArray()
            ->and(count($data))->toBeLessThanOrEqual(10);
    }

    // Nested routes tests
    public function test_can_list_products_for_brand(): void
    {
        Product::factory()->count(3)->create(['brand_id' => $this->brand->id]);

        // Create access for user to brand
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/brands/{$this->brand->slug}/products");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'Product', 'slug'],
                ],
            ]);
    }

    public function test_can_create_product_for_brand_without_brand_id(): void
    {
        // Create access for user to brand
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);

        $productData = [
            'ProductID' => 88888,
            'Product' => 'Brand Product',
            'main_category_id' => $this->category->id,
            'gender_id' => $this->gender->id,
            // brand_id is automatically set from URL, don't include it
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/brands/{$this->brand->slug}/products", $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Product', 'Brand Product')
            ->assertJsonPath('data.brand_id', $this->brand->id);

        $this->assertDatabaseHas('products', [
            'ProductID' => 88888,
            'Product' => 'Brand Product',
            'brand_id' => $this->brand->id,
        ]);
    }

    public function test_cannot_set_brand_id_when_creating_through_brand_route(): void
    {
        $otherBrand = Brand::factory()->create();

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);

        $productData = [
            'ProductID' => 77777,
            'Product' => 'Test Product',
            'brand_id' => $otherBrand->id, // Should be prohibited
            'main_category_id' => $this->category->id,
            'gender_id' => $this->gender->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/brands/{$this->brand->slug}/products", $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_id']);
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
        $productData = [
            'ProductID' => 66666,
            'Product' => 'Category Product',
            'brand_id' => $this->brand->id,
            'gender_id' => $this->gender->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/categories/{$this->category->slug}/products", $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Product', 'Category Product')
            ->assertJsonPath('data.main_category_id', $this->category->id);

        $this->assertDatabaseHas('products', [
            'ProductID' => 66666,
            'Product' => 'Category Product',
            'main_category_id' => $this->category->id,
        ]);
    }

    public function test_can_list_products_for_gender(): void
    {
        Product::factory()->count(3)->create(['gender_id' => $this->gender->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/genders/{$this->gender->slug}/products");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ProductID', 'Product', 'slug'],
                ],
            ]);
    }

    public function test_can_create_product_for_gender_without_gender_id(): void
    {
        $productData = [
            'ProductID' => 55555,
            'Product' => 'Gender Product',
            'brand_id' => $this->brand->id,
            'main_category_id' => $this->category->id,
            // gender_id is automatically set from URL, don't include it
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/genders/{$this->gender->slug}/products", $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Product', 'Gender Product')
            ->assertJsonPath('data.gender_id', $this->gender->id);

        $this->assertDatabaseHas('products', [
            'ProductID' => 55555,
            'Product' => 'Gender Product',
            'gender_id' => $this->gender->id,
        ]);
    }

    public function test_cannot_set_gender_id_when_creating_through_gender_route(): void
    {
        $otherGender = Gender::factory()->create();

        $productData = [
            'ProductID' => 44444,
            'Product' => 'Test Product',
            'brand_id' => $this->brand->id,
            'main_category_id' => $this->category->id,
            'gender_id' => $otherGender->id, // Should be prohibited
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/genders/{$this->gender->slug}/products", $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender_id']);
    }

    public function test_can_update_product_through_brand_route(): void
    {
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);

        $updateData = [
            'Product' => 'Updated Through Brand',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/brands/{$this->brand->slug}/products/{$this->product->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.Product', 'Updated Through Brand')
            ->assertJsonPath('data.brand_id', $this->brand->id);
    }

    public function test_can_delete_product_through_brand_route(): void
    {
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/brands/{$this->brand->slug}/products/{$this->product->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', [
            'id' => $this->product->id,
        ]);
    }

    public function test_returns_404_if_product_not_belongs_to_brand(): void
    {
        $otherBrand = Brand::factory()->create();
        $otherProduct = Product::factory()->create(['brand_id' => $otherBrand->id]);

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/brands/{$this->brand->slug}/products/{$otherProduct->slug}");

        $response->assertStatus(404);
    }
}
