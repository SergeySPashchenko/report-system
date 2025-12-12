<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Models\Company;
use App\Queries\CompanyQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyQueryTest extends TestCase
{
    use RefreshDatabase;

    private CompanyQuery $companyQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyQuery = new CompanyQuery();
    }

    public function test_can_search_by_name(): void
    {
        Company::factory()->create(['name' => 'Tech Corp']);
        Company::factory()->create(['name' => 'Finance Inc']);

        $results = $this->companyQuery->reset()->search('Tech')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Tech Corp');
    }

    public function test_can_search_by_slug(): void
    {
        Company::factory()->create(['slug' => 'tech-corp']);
        Company::factory()->create(['slug' => 'finance-inc']);

        $results = $this->companyQuery->reset()->search('tech-corp')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->slug)->toBe('tech-corp');
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        Company::factory()->create(['name' => 'Tech Corp']);

        $results = $this->companyQuery->reset()->search('Nonexistent')->get();

        expect($results)->toHaveCount(0);
    }

    public function test_search_ignores_empty_string(): void
    {
        Company::factory()->count(3)->create();

        $results = $this->companyQuery->reset()->search('')->get();

        expect($results)->toHaveCount(3);
    }

    public function test_can_sort_by_column_ascending(): void
    {
        Company::factory()->create(['name' => 'Charlie']);
        Company::factory()->create(['name' => 'Alpha']);
        Company::factory()->create(['name' => 'Beta']);

        $results = $this->companyQuery->reset()->sort('name', 'asc')->get();

        $names = $results->pluck('name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_can_sort_by_column_descending(): void
    {
        Company::factory()->create(['name' => 'Alpha']);
        Company::factory()->create(['name' => 'Beta']);
        Company::factory()->create(['name' => 'Charlie']);

        $results = $this->companyQuery->reset()->sort('name', 'desc')->get();

        $names = $results->pluck('name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_sort_without_column_uses_latest(): void
    {
        Company::factory()->create(['created_at' => now()->subDays(5)]);
        $newCompany = Company::factory()->create(['created_at' => now()]);

        $results = $this->companyQuery->reset()->sort(null)->get();

        expect($results->first()->id)->toBe($newCompany->id);
    }

    public function test_can_filter_by_name(): void
    {
        Company::factory()->create(['name' => 'Tech Corp']);
        Company::factory()->create(['name' => 'Finance Inc']);

        $results = $this->companyQuery->reset()->byName('Tech Corp')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Tech Corp');
    }

    public function test_can_filter_by_slug(): void
    {
        Company::factory()->create(['slug' => 'tech-corp']);
        Company::factory()->create(['slug' => 'finance-inc']);

        $results = $this->companyQuery->reset()->bySlug('tech-corp')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->slug)->toBe('tech-corp');
    }

    public function test_can_limit_results(): void
    {
        Company::factory()->count(10)->create();

        $results = $this->companyQuery->reset()->limit(5)->get();

        expect($results)->toHaveCount(5);
    }

    public function test_can_paginate_results(): void
    {
        Company::factory()->count(25)->create();

        $paginator = $this->companyQuery->reset()->paginate(10);

        expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginator->perPage())->toBe(10)
            ->and($paginator->total())->toBe(25);
    }

    public function test_can_find_by_slug(): void
    {
        $company = Company::factory()->create(['slug' => 'test-company']);

        $found = $this->companyQuery->reset()->findBySlug('test-company');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $found = $this->companyQuery->reset()->findBySlug('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_reset_clears_query(): void
    {
        Company::factory()->count(3)->create();

        $this->companyQuery->search('test')->sort('name');

        $resetQuery = $this->companyQuery->reset();

        expect($resetQuery)->toBeInstanceOf(CompanyQuery::class);

        $allCompanies = $resetQuery->get();
        expect($allCompanies->count())->toBe(3);
    }

    public function test_can_chain_methods(): void
    {
        Company::factory()->create(['name' => 'Alpha Tech']);
        Company::factory()->create(['name' => 'Beta Finance']);
        Company::factory()->create(['name' => 'Gamma Tech']);

        $results = $this->companyQuery
            ->reset()
            ->search('Tech')
            ->sort('name', 'asc')
            ->get();

        expect($results)->toHaveCount(2)
            ->and($results->first()->name)->toBe('Alpha Tech');
    }
}
