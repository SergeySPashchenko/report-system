<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Queries\CompanyQuery;
use App\Services\CompanyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyService $companyService;

    private CompanyQuery $companyQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyQuery = new CompanyQuery();
        $this->companyService = new CompanyService($this->companyQuery);
    }

    public function test_can_get_paginated_companies(): void
    {
        Company::factory()->count(20)->create();

        $result = $this->companyService->getPaginatedCompanies();

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->count())->toBeGreaterThan(0)
            ->and($result->perPage())->toBe(15);
    }

    public function test_can_search_companies(): void
    {
        Company::factory()->create(['name' => 'Tech Corp', 'slug' => 'tech-corp']);
        Company::factory()->create(['name' => 'Finance Inc', 'slug' => 'finance-inc']);

        $result = $this->companyService->getPaginatedCompanies(search: 'Tech');

        expect($result->count())->toBe(1)
            ->and($result->first()->name)->toBe('Tech Corp');
    }

    public function test_can_sort_companies(): void
    {
        Company::factory()->create(['name' => 'Charlie']);
        Company::factory()->create(['name' => 'Alpha']);
        Company::factory()->create(['name' => 'Beta']);

        $result = $this->companyService->getPaginatedCompanies(sortBy: 'name', sortDirection: 'asc');

        $names = $result->pluck('name')->toArray();
        expect($names)->toContain('Alpha', 'Beta', 'Charlie');
    }

    public function test_can_find_company_by_slug(): void
    {
        $company = Company::factory()->create(['slug' => 'test-company']);

        $found = $this->companyService->findBySlug('test-company');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id)
            ->and($found->slug)->toBe('test-company');
    }

    public function test_returns_null_when_slug_not_found(): void
    {
        $found = $this->companyService->findBySlug('nonexistent');

        expect($found)->toBeNull();
    }

    public function test_can_create_company(): void
    {
        $data = [
            'name' => 'New Company',
        ];

        $company = $this->companyService->create($data);

        expect($company)->toBeInstanceOf(Company::class)
            ->and($company->name)->toBe('New Company');

        $this->assertDatabaseHas('companies', [
            'name' => 'New Company',
        ]);
    }

    public function test_can_update_company(): void
    {
        $company = Company::factory()->create(['name' => 'Old Name']);

        $updated = $this->companyService->update($company, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $result = $this->companyService->delete($company);

        expect($result)->toBeTrue();

        $this->assertSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_can_restore_company(): void
    {
        $company = Company::factory()->create();
        $company->delete();

        $restored = $this->companyService->restore($company->id);

        expect($restored->id)->toBe($company->id);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_company(): void
    {
        $company = Company::factory()->create();
        $companyId = $company->id;

        $result = $this->companyService->forceDelete($companyId);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('companies', [
            'id' => $companyId,
        ]);
    }

    public function test_can_get_statistics(): void
    {
        Company::factory()->count(5)->create();
        $deleted = Company::factory()->create();
        $deleted->delete();

        $stats = $this->companyService->getStatistics();

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['total', 'deleted', 'created_today', 'created_this_week', 'created_this_month'])
            ->and($stats['total'])->toBeGreaterThan(0)
            ->and($stats['deleted'])->toBe(1);
    }
}
