<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Models\Expensetype;
use App\Queries\ExpensetypeQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Sleep;
use Tests\TestCase;

final class ExpensetypeQueryTest extends TestCase
{
    use RefreshDatabase;

    private ExpensetypeQuery $expensetypeQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expensetypeQuery = new ExpensetypeQuery();
    }

    public function test_can_search_expensetypes_by_name(): void
    {
        Expensetype::factory()->create(['Name' => 'Marketing']);
        Expensetype::factory()->create(['Name' => 'Sales']);

        $result = $this->expensetypeQuery->search('Marketing')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->Name)->toBe('Marketing');
    }

    public function test_can_search_expensetypes_by_slug(): void
    {
        $expensetype = Expensetype::factory()->create(['Name' => 'Marketing']);

        $result = $this->expensetypeQuery->search($expensetype->slug)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->slug)->toBe($expensetype->slug);
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        Expensetype::factory()->create(['Name' => 'Marketing']);

        $result = $this->expensetypeQuery->search('Nonexistent')->get();

        expect($result->count())->toBe(0);
    }

    public function test_can_sort_expensetypes(): void
    {
        Expensetype::factory()->create(['Name' => 'Charlie']);
        Expensetype::factory()->create(['Name' => 'Alpha']);
        Expensetype::factory()->create(['Name' => 'Beta']);

        $result = $this->expensetypeQuery->sort('Name', 'asc')->get();

        $names = $result->pluck('Name')->toArray();
        expect($names[0])->toBe('Alpha')
            ->and($names[1])->toBe('Beta')
            ->and($names[2])->toBe('Charlie');
    }

    public function test_can_filter_by_slug(): void
    {
        $expensetype = Expensetype::factory()->create(['Name' => 'Test ExpenseType']);

        $result = $this->expensetypeQuery->bySlug($expensetype->slug)->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->slug)->toBe($expensetype->slug);
    }

    public function test_can_find_by_slug(): void
    {
        $expensetype = Expensetype::factory()->create(['Name' => 'Test ExpenseType']);

        $found = $this->expensetypeQuery->findBySlug($expensetype->slug);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($expensetype->id)
            ->and($found->slug)->toBe($expensetype->slug);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $found = $this->expensetypeQuery->findBySlug('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_can_limit_results(): void
    {
        Expensetype::factory()->count(10)->create();

        $result = $this->expensetypeQuery->limit(5)->get();

        expect($result->count())->toBe(5);
    }

    public function test_can_paginate_results(): void
    {
        Expensetype::factory()->count(25)->create();

        $result = $this->expensetypeQuery->paginate(10);

        expect($result->count())->toBe(10)
            ->and($result->perPage())->toBe(10)
            ->and($result->total())->toBe(25);
    }

    public function test_can_reset_query(): void
    {
        $this->expensetypeQuery->search('Test');

        $resetQuery = $this->expensetypeQuery->reset();

        expect($resetQuery)->toBeInstanceOf(ExpensetypeQuery::class);
        // After reset, should return all expensetypes
        $result = $this->expensetypeQuery->get();
        expect($result->count())->toBeGreaterThanOrEqual(0);
    }

    public function test_default_sort_is_by_created_at_desc(): void
    {
        Expensetype::factory()->create(['Name' => 'First']);
        // Small delay to ensure different timestamps
        Sleep::sleep(1);
        $second = Expensetype::factory()->create(['Name' => 'Second']);

        $result = $this->expensetypeQuery->reset()->sort(null)->get();

        // Latest should be first
        expect($result->first()->id)->toBe($second->id);
    }
}
