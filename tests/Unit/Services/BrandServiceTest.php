<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Brand;
use App\Queries\BrandQuery;
use App\Services\BrandService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandServiceTest extends TestCase
{
    use RefreshDatabase;

    private BrandService $brandService;

    private BrandQuery $brandQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brandQuery = new BrandQuery();
        $this->brandService = new BrandService($this->brandQuery);
    }

    public function test_can_get_paginated_brands(): void
    {
        Brand::factory()->count(20)->create();

        $result = $this->brandService->getPaginatedBrands();

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBeGreaterThan(0)
            ->and($result->perPage())->toBe(15);
    }

    public function test_can_search_brands(): void
    {
        Brand::factory()->create(['name' => 'Tech Brand', 'slug' => 'tech-brand']);
        Brand::factory()->create(['name' => 'Fashion Brand', 'slug' => 'fashion-brand']);

        $result = $this->brandService->getPaginatedBrands(search: 'Tech');

        expect($result->count())->toBe(1)
            ->and($result->first()->name)->toBe('Tech Brand');
    }

    public function test_can_sort_brands(): void
    {
        Brand::factory()->create(['name' => 'Charlie']);
        Brand::factory()->create(['name' => 'Alpha']);
        Brand::factory()->create(['name' => 'Beta']);

        $result = $this->brandService->getPaginatedBrands(sortBy: 'name', sortDirection: 'asc');

        $names = $result->pluck('name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_can_find_brand_by_slug(): void
    {
        $brand = Brand::factory()->create(['slug' => 'test-brand']);

        $found = $this->brandService->findBySlug('test-brand');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($brand->id)
            ->and($found->slug)->toBe('test-brand');
    }

    public function test_returns_null_when_slug_not_found(): void
    {
        $found = $this->brandService->findBySlug('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_can_create_brand(): void
    {
        $data = [
            'name' => 'New Brand',
        ];

        $brand = $this->brandService->create($data);

        expect($brand)->toBeInstanceOf(Brand::class)
            ->and($brand->name)->toBe('New Brand');

        $this->assertDatabaseHas('brands', [
            'name' => 'New Brand',
        ]);
    }

    public function test_can_update_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Name']);

        $updated = $this->brandService->update($brand, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_delete_brand(): void
    {
        $brand = Brand::factory()->create();

        $result = $this->brandService->delete($brand);

        expect($result)->toBeTrue();

        $this->assertSoftDeleted('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_can_restore_brand(): void
    {
        $brand = Brand::factory()->create();
        $brand->delete();

        $restored = $this->brandService->restore($brand->id);

        expect($restored->id)->toBe($brand->id);

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_brand(): void
    {
        $brand = Brand::factory()->create();
        $brandId = $brand->id;

        $result = $this->brandService->forceDelete($brandId);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('brands', [
            'id' => $brandId,
        ]);
    }

    public function test_can_get_statistics(): void
    {
        Brand::factory()->count(5)->create();
        $deleted = Brand::factory()->create();
        $deleted->delete();

        $stats = $this->brandService->getStatistics();

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['total', 'deleted', 'created_today', 'created_this_week', 'created_this_month'])
            ->and($stats['total'])->toBeGreaterThan(0)
            ->and($stats['deleted'])->toBe(1);
    }
}
