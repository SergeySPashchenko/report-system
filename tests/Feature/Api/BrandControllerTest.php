<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Access;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->brand = Brand::factory()->create(['name' => 'Test Brand']);

        // Create access for user to brand
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);
    }

    public function test_can_list_brands(): void
    {
        Brand::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/brands');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_brands(): void
    {
        Brand::factory()->create(['name' => 'Tech Brand']);
        Brand::factory()->create(['name' => 'Fashion Brand']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/brands?search=Tech');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tech Brand');
    }

    public function test_can_show_brand(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/brands/{$this->brand->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->brand->id)
            ->assertJsonPath('data.name', $this->brand->name);
    }

    public function test_can_create_brand(): void
    {
        $brandData = [
            'name' => 'New Brand',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/brands', $brandData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Brand');

        $this->assertDatabaseHas('brands', [
            'name' => 'New Brand',
        ]);
    }

    public function test_can_update_brand_with_access(): void
    {
        $updateData = [
            'name' => 'Updated Brand Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/brands/{$this->brand->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Brand Name');

        $this->assertDatabaseHas('brands', [
            'id' => $this->brand->id,
            'name' => 'Updated Brand Name',
        ]);
    }

    public function test_cannot_update_brand_without_access(): void
    {
        $otherBrand = Brand::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/brands/{$otherBrand->slug}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_brand_with_access(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/brands/{$this->brand->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('brands', [
            'id' => $this->brand->id,
        ]);
    }

    public function test_cannot_delete_brand_without_access(): void
    {
        $otherBrand = Brand::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/v1/brands/{$otherBrand->slug}");

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_duplicate_name(): void
    {
        Brand::factory()->create(['name' => 'Existing Brand']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/brands', [
                'name' => 'Existing Brand',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_fails_for_missing_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/brands', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_guest_cannot_access_brands_endpoint(): void
    {
        $response = $this->getJson('/api/v1/brands');

        $response->assertStatus(401);
    }

    public function test_can_get_brand_statistics(): void
    {
        Brand::factory()->count(5)->create();
        $deleted = Brand::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/brands/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'created_today',
                'created_this_week',
                'created_this_month',
            ])
            ->assertJsonPath('total', 6) // 5 + 1 deleted (brand from setUp is not counted in total as it's soft deleted)
            ->assertJsonPath('deleted', 1);
    }

    public function test_can_restore_soft_deleted_brand(): void
    {
        $deletedBrand = Brand::factory()->create();

        // Create access before deleting
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $deletedBrand->id,
            'accessible_type' => 'brand',
        ]);

        $deletedBrand->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/brands/{$deletedBrand->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $deletedBrand->id);

        $this->assertDatabaseHas('brands', [
            'id' => $deletedBrand->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_brand(): void
    {
        $brandToDelete = Brand::factory()->create();

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $brandToDelete->id,
            'accessible_type' => 'brand',
        ]);

        $brandId = $brandToDelete->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/brands/{$brandId}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('brands', [
            'id' => $brandId,
        ]);
    }

    public function test_can_sort_brands(): void
    {
        Brand::factory()->create(['name' => 'Alpha']);
        Brand::factory()->create(['name' => 'Beta']);
        Brand::factory()->create(['name' => 'Gamma']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/brands?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $brands = $response->json('data');
        $names = array_column($brands, 'name');
        $this->assertContains('Alpha', $names);
        $this->assertContains('Beta', $names);
        $this->assertContains('Gamma', $names);
    }

    public function test_can_paginate_brands(): void
    {
        Brand::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/brands?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $perPage = is_array($meta['per_page']) ? $meta['per_page'][0] : $meta['per_page'];
        expect($perPage)->toBe(10);

        $data = $response->json('data');
        expect($data)->toBeArray()
            ->and(count($data))->toBeLessThanOrEqual(10);
    }
}
