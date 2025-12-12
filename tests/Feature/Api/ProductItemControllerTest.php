<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Access;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProductItem $productItem;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['ProductID' => 12345]);
        $this->productItem = ProductItem::factory()->create([
            'ItemID' => 1,
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'Test Product Item',
            'SKU' => 'TEST-SKU',
            'Quantity' => 10,
        ]);

        // Create access for user to product
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->product->id,
            'accessible_type' => 'product',
        ]);
    }

    public function test_can_list_product_items(): void
    {
        ProductItem::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/product-items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ItemID', 'ProductID', 'ProductName', 'slug', 'SKU', 'Quantity'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_show_product_item(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/product-items/{$this->productItem->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->productItem->id)
            ->assertJsonPath('data.ProductName', $this->productItem->ProductName);
    }

    public function test_can_create_product_item(): void
    {
        $productItemData = [
            'ItemID' => 999,
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'New Product Item',
            'SKU' => 'NEW-SKU',
            'Quantity' => 5,
            'upSell' => false,
            'extraProduct' => false,
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product-items', $productItemData);

        $response->assertStatus(201)
            ->assertJsonPath('data.ProductName', 'New Product Item');

        $this->assertDatabaseHas('product_items', [
            'ItemID' => 999,
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'New Product Item',
        ]);
    }

    public function test_can_create_product_item_without_item_id(): void
    {
        $productItemData = [
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'New Product Item Without ItemID',
            'SKU' => 'NEW-SKU-2',
            'Quantity' => 5,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product-items', $productItemData);

        $response->assertStatus(201)
            ->assertJsonPath('data.ProductName', 'New Product Item Without ItemID');

        $this->assertDatabaseHas('product_items', [
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'New Product Item Without ItemID',
        ]);
    }

    public function test_can_update_product_item_with_access(): void
    {
        $updateData = [
            'ProductName' => 'Updated Product Item Name',
            'Quantity' => 20,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/product-items/{$this->productItem->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.ProductName', 'Updated Product Item Name')
            ->assertJsonPath('data.Quantity', 20);

        $this->assertDatabaseHas('product_items', [
            'id' => $this->productItem->id,
            'ProductName' => 'Updated Product Item Name',
            'Quantity' => 20,
        ]);
    }

    public function test_cannot_update_product_item_without_access(): void
    {
        $otherProduct = Product::factory()->create();
        $otherProductItem = ProductItem::factory()->create([
            'ProductID' => $otherProduct->ProductID,
        ]);
        $otherUser = User::factory()->create();

        // Видаляємо доступ до компанії для користувача
        $otherUser->accesses()->where('accessible_type', 'company')->delete();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/product-items/{$otherProductItem->slug}", [
                'ProductName' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_product_item_with_access(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/product-items/{$this->productItem->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('product_items', [
            'id' => $this->productItem->id,
        ]);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product-items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID', 'ProductName', 'SKU', 'Quantity']);
    }

    public function test_guest_cannot_access_product_items_endpoint(): void
    {
        $response = $this->getJson('/api/v1/product-items');

        $response->assertStatus(401);
    }

    public function test_can_get_product_item_statistics(): void
    {
        ProductItem::factory()->count(5)->create([
            'ProductID' => $this->product->ProductID,
        ]);
        $deleted = ProductItem::factory()->create([
            'ProductID' => $this->product->ProductID,
        ]);
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/product-items/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'active',
                'inactive',
                'upSell',
                'extraProduct',
                'created_today',
                'created_this_week',
                'created_this_month',
            ]);
    }

    public function test_can_list_product_items_for_product(): void
    {
        ProductItem::factory()->count(3)->create([
            'ProductID' => $this->product->ProductID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}/product-items");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ItemID', 'ProductID', 'ProductName', 'slug', 'SKU'],
                ],
            ]);
    }

    public function test_can_create_product_item_for_product_without_product_id(): void
    {
        $productItemData = [
            'ProductName' => 'Product Item for Product',
            'SKU' => 'PROD-SKU',
            'Quantity' => 15,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/products/{$this->product->slug}/product-items", $productItemData);

        $response->assertStatus(201)
            ->assertJsonPath('data.ProductName', 'Product Item for Product')
            ->assertJsonPath('data.ProductID', $this->product->ProductID);

        $this->assertDatabaseHas('product_items', [
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'Product Item for Product',
        ]);
    }

    public function test_cannot_set_product_id_when_creating_through_product_route(): void
    {
        $otherProduct = Product::factory()->create();

        $productItemData = [
            'ProductID' => $otherProduct->ProductID, // Should be prohibited
            'ProductName' => 'Test Product Item',
            'SKU' => 'TEST-SKU',
            'Quantity' => 10,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/products/{$this->product->slug}/product-items", $productItemData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID']);
    }

    public function test_can_show_product_item_for_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}/product-items/{$this->productItem->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->productItem->id)
            ->assertJsonPath('data.ProductID', $this->product->ProductID);
    }

    public function test_returns_404_if_product_item_not_belongs_to_product(): void
    {
        $otherProduct = Product::factory()->create();
        $otherProductItem = ProductItem::factory()->create([
            'ProductID' => $otherProduct->ProductID,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$this->product->slug}/product-items/{$otherProductItem->slug}");

        $response->assertStatus(404);
    }

    public function test_can_update_product_item_for_product(): void
    {
        $updateData = [
            'ProductName' => 'Updated Through Product',
            'Quantity' => 25,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$this->product->slug}/product-items/{$this->productItem->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.ProductName', 'Updated Through Product')
            ->assertJsonPath('data.ProductID', $this->product->ProductID);
    }

    public function test_cannot_change_product_id_when_updating_through_product_route(): void
    {
        $otherProduct = Product::factory()->create();

        $updateData = [
            'ProductID' => $otherProduct->ProductID, // Should be prohibited
            'ProductName' => 'Hacked Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$this->product->slug}/product-items/{$this->productItem->slug}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ProductID']);
    }

    public function test_can_delete_product_item_for_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$this->product->slug}/product-items/{$this->productItem->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('product_items', [
            'id' => $this->productItem->id,
        ]);
    }

    public function test_can_restore_product_item(): void
    {
        $this->productItem->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/product-items/{$this->productItem->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->productItem->id);

        $this->assertDatabaseHas('product_items', [
            'id' => $this->productItem->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_product_item(): void
    {
        $this->productItem->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/product-items/{$this->productItem->id}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('product_items', [
            'id' => $this->productItem->id,
        ]);
    }

    public function test_can_filter_product_items_by_active(): void
    {
        // Видаляємо productItem з setUp, щоб не заважав тесту
        $this->productItem->delete();

        ProductItem::factory()->create([
            'ProductID' => $this->product->ProductID,
            'active' => true,
        ]);
        ProductItem::factory()->create([
            'ProductID' => $this->product->ProductID,
            'active' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/product-items?active=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        // Перевіряємо що всі повернуті елементи мають active=true
        foreach ($data as $item) {
            $this->assertTrue($item['active'], 'All items should be active when filtered by active=true');
        }
    }

    public function test_can_search_product_items(): void
    {
        ProductItem::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'Premium Item',
            'SKU' => 'PREM-SKU',
        ]);
        ProductItem::factory()->create([
            'ProductID' => $this->product->ProductID,
            'ProductName' => 'Standard Item',
            'SKU' => 'STD-SKU',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/product-items?search=Premium');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }
}
