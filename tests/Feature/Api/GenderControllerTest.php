<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GenderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Gender $gender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->gender = Gender::factory()->create(['gender_name' => 'Test Gender']);
    }

    public function test_can_list_genders(): void
    {
        Gender::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/genders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'gender_name', 'slug', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_genders(): void
    {
        Gender::factory()->create(['gender_name' => 'Male']);
        Gender::factory()->create(['gender_name' => 'Female']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/genders?search=Male');

        $response->assertStatus(200);
    }

    public function test_can_show_gender(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/genders/{$this->gender->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->gender->id)
            ->assertJsonPath('data.gender_name', $this->gender->gender_name);
    }

    public function test_can_create_gender(): void
    {
        $genderData = [
            'gender_name' => 'New Gender',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/genders', $genderData);

        $response->assertStatus(201)
            ->assertJsonPath('data.gender_name', 'New Gender');

        $this->assertDatabaseHas('genders', [
            'gender_name' => 'New Gender',
        ]);
    }

    public function test_can_update_gender(): void
    {
        $updateData = [
            'gender_name' => 'Updated Gender Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/genders/{$this->gender->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.gender_name', 'Updated Gender Name');

        $this->assertDatabaseHas('genders', [
            'id' => $this->gender->id,
            'gender_name' => 'Updated Gender Name',
        ]);
    }

    public function test_can_delete_gender(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/genders/{$this->gender->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('genders', [
            'id' => $this->gender->id,
        ]);
    }

    public function test_validation_fails_for_missing_gender_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/genders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender_name']);
    }

    public function test_guest_cannot_access_genders_endpoint(): void
    {
        $response = $this->getJson('/api/v1/genders');

        $response->assertStatus(401);
    }

    public function test_can_get_gender_statistics(): void
    {
        Gender::factory()->count(5)->create();
        $deleted = Gender::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/genders/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'created_today',
                'created_this_week',
                'created_this_month',
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
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $productData = [
            'ProductID' => 22222,
            'Product' => 'Gender Product',
            'brand_id' => $brand->id,
            'main_category_id' => $category->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/genders/{$this->gender->slug}/products", $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.Product', 'Gender Product')
            ->assertJsonPath('data.gender_id', $this->gender->id);

        $this->assertDatabaseHas('products', [
            'ProductID' => 22222,
            'Product' => 'Gender Product',
            'gender_id' => $this->gender->id,
        ]);
    }

    public function test_returns_404_if_product_not_belongs_to_gender(): void
    {
        $otherGender = Gender::factory()->create();
        $otherProduct = Product::factory()->create(['gender_id' => $otherGender->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/genders/{$this->gender->slug}/products/{$otherProduct->slug}");

        $response->assertStatus(404);
    }
}
