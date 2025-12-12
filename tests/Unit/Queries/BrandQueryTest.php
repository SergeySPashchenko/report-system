<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Models\Brand;
use App\Queries\BrandQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandQueryTest extends TestCase
{
    use RefreshDatabase;

    private BrandQuery $brandQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brandQuery = new BrandQuery();
    }

    public function test_can_search_by_name(): void
    {
        Brand::factory()->create(['name' => 'Tech Brand']);
        Brand::factory()->create(['name' => 'Fashion Brand']);

        $results = $this->brandQuery->reset()->search('Tech')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Tech Brand');
    }

    public function test_can_search_by_slug(): void
    {
        Brand::factory()->create(['slug' => 'tech-brand']);
        Brand::factory()->create(['slug' => 'fashion-brand']);

        $results = $this->brandQuery->reset()->search('tech-brand')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->slug)->toBe('tech-brand');
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        Brand::factory()->create(['name' => 'Tech Brand']);

        $results = $this->brandQuery->reset()->search('Nonexistent')->get();

        expect($results)->toHaveCount(0);
    }

    public function test_search_ignores_empty_string(): void
    {
        Brand::factory()->count(3)->create();

        $results = $this->brandQuery->reset()->search('')->get();

        expect($results)->toHaveCount(3);
    }

    public function test_can_sort_by_column_ascending(): void
    {
        Brand::factory()->create(['name' => 'Charlie']);
        Brand::factory()->create(['name' => 'Alpha']);
        Brand::factory()->create(['name' => 'Beta']);

        $results = $this->brandQuery->reset()->sort('name', 'asc')->get();

        $names = $results->pluck('name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_can_sort_by_column_descending(): void
    {
        Brand::factory()->create(['name' => 'Alpha']);
        Brand::factory()->create(['name' => 'Beta']);
        Brand::factory()->create(['name' => 'Charlie']);

        $results = $this->brandQuery->reset()->sort('name', 'desc')->get();

        $names = $results->pluck('name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_sort_without_column_uses_latest(): void
    {
        Brand::factory()->create(['created_at' => now()->subDays(5)]);
        $newBrand = Brand::factory()->create(['created_at' => now()]);

        $results = $this->brandQuery->reset()->sort(null)->get();

        expect($results->first()->id)->toBe($newBrand->id);
    }

    public function test_can_filter_by_name(): void
    {
        Brand::factory()->create(['name' => 'Tech Brand']);
        Brand::factory()->create(['name' => 'Fashion Brand']);

        $results = $this->brandQuery->reset()->byName('Tech Brand')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Tech Brand');
    }

    public function test_can_filter_by_slug(): void
    {
        Brand::factory()->create(['slug' => 'tech-brand']);
        Brand::factory()->create(['slug' => 'fashion-brand']);

        $results = $this->brandQuery->reset()->bySlug('tech-brand')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->slug)->toBe('tech-brand');
    }

    public function test_can_limit_results(): void
    {
        Brand::factory()->count(10)->create();

        $results = $this->brandQuery->reset()->limit(5)->get();

        expect($results)->toHaveCount(5);
    }

    public function test_can_paginate_results(): void
    {
        Brand::factory()->count(25)->create();

        $paginator = $this->brandQuery->reset()->paginate(10);

        expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginator->perPage())->toBe(10)
            ->and($paginator->total())->toBe(25);
    }

    public function test_can_find_by_slug(): void
    {
        $brand = Brand::factory()->create(['slug' => 'test-brand']);

        $found = $this->brandQuery->reset()->findBySlug('test-brand');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($brand->id);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $found = $this->brandQuery->reset()->findBySlug('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_reset_clears_query(): void
    {
        Brand::factory()->count(3)->create();

        $this->brandQuery->search('test')->sort('name');

        $resetQuery = $this->brandQuery->reset();

        expect($resetQuery)->toBeInstanceOf(BrandQuery::class);

        $allBrands = $resetQuery->get();
        expect($allBrands->count())->toBe(3);
    }

    public function test_can_chain_methods(): void
    {
        Brand::factory()->create(['name' => 'Alpha Tech']);
        Brand::factory()->create(['name' => 'Beta Fashion']);
        Brand::factory()->create(['name' => 'Gamma Tech']);

        $results = $this->brandQuery
            ->reset()
            ->search('Tech')
            ->sort('name', 'asc')
            ->get();

        expect($results)->toHaveCount(2)
            ->and($results->first()->name)->toBe('Alpha Tech');
    }
}
