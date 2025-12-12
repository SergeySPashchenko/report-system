<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Access;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create(['name' => 'Test Company']);

        // Create access for user to company
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->company->id,
            'accessible_type' => Company::class,
        ]);
    }

    public function test_can_list_companies(): void
    {
        Company::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'created_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_companies(): void
    {
        Company::factory()->create(['name' => 'Tech Corp']);
        Company::factory()->create(['name' => 'Finance Inc']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/companies?search=Tech');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tech Corp');
    }

    public function test_can_show_company(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/companies/{$this->company->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->company->id)
            ->assertJsonPath('data.name', $this->company->name);
    }

    public function test_can_create_company(): void
    {
        $companyData = [
            'name' => 'New Company',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/companies', $companyData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Company');

        $this->assertDatabaseHas('companies', [
            'name' => 'New Company',
        ]);
    }

    public function test_can_update_company_with_access(): void
    {
        $updateData = [
            'name' => 'Updated Company Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/companies/{$this->company->slug}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Company Name');

        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'Updated Company Name',
        ]);
    }

    public function test_cannot_update_company_without_access(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/companies/{$otherCompany->slug}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_company_with_access(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/companies/{$this->company->slug}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('companies', [
            'id' => $this->company->id,
        ]);
    }

    public function test_cannot_delete_main_company(): void
    {
        $mainCompany = Company::factory()->create(['name' => 'Main']);

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $mainCompany->id,
            'accessible_type' => Company::class,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/companies/{$mainCompany->slug}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_company_without_access(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/v1/companies/{$otherCompany->slug}");

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_duplicate_name(): void
    {
        Company::factory()->create(['name' => 'Existing Company']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/companies', [
                'name' => 'Existing Company',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_fails_for_missing_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/companies', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_guest_cannot_access_companies_endpoint(): void
    {
        $response = $this->getJson('/api/v1/companies');

        $response->assertStatus(401);
    }

    public function test_can_get_company_statistics(): void
    {
        Company::factory()->count(5)->create();
        $deleted = Company::factory()->create();
        $deleted->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/companies/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'deleted',
                'created_today',
                'created_this_week',
                'created_this_month',
            ])
            ->assertJsonPath('total', 7); // 5 + 1 deleted + 1 from setUp
    }

    public function test_can_restore_soft_deleted_company(): void
    {
        $deletedCompany = Company::factory()->create();

        // Create access before deleting
        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $deletedCompany->id,
            'accessible_type' => Company::class,
        ]);

        $deletedCompany->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/companies/{$deletedCompany->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $deletedCompany->id);

        $this->assertDatabaseHas('companies', [
            'id' => $deletedCompany->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_force_delete_company(): void
    {
        $companyToDelete = Company::factory()->create();

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $companyToDelete->id,
            'accessible_type' => Company::class,
        ]);

        $companyId = $companyToDelete->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/companies/{$companyId}/force");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('companies', [
            'id' => $companyId,
        ]);
    }

    public function test_cannot_force_delete_main_company(): void
    {
        $mainCompany = Company::factory()->create(['name' => 'Main']);

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $mainCompany->id,
            'accessible_type' => Company::class,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/companies/{$mainCompany->id}/force");

        $response->assertStatus(403);
    }

    public function test_can_sort_companies(): void
    {
        Company::factory()->create(['name' => 'Alpha']);
        Company::factory()->create(['name' => 'Beta']);
        Company::factory()->create(['name' => 'Gamma']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/companies?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $companies = $response->json('data');
        $names = array_column($companies, 'name');
        $this->assertContains('Alpha', $names);
        $this->assertContains('Beta', $names);
        $this->assertContains('Gamma', $names);
    }

    public function test_can_paginate_companies(): void
    {
        Company::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/companies?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $perPage = is_array($meta['per_page']) ? $meta['per_page'][0] : $meta['per_page'];
        expect($perPage)->toBe(10);

        $data = $response->json('data');
        expect($data)->toBeArray()
            ->and(count($data))->toBeLessThanOrEqual(10);
    }
}
