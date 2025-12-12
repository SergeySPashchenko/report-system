<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->company->id,
            'accessible_type' => Company::class,
        ]);
    }

    public function test_user_can_view_any_companies(): void
    {
        expect($this->user->can('viewAny', Company::class))->toBeTrue();
    }

    public function test_user_can_view_company(): void
    {
        expect($this->user->can('view', $this->company))->toBeTrue();
    }

    public function test_user_can_create_company(): void
    {
        expect($this->user->can('create', Company::class))->toBeTrue();
    }

    public function test_user_can_update_company_with_access(): void
    {
        expect($this->user->can('update', $this->company))->toBeTrue();
    }

    public function test_user_cannot_update_company_without_access(): void
    {
        $otherCompany = Company::factory()->create();

        expect($this->user->can('update', $otherCompany))->toBeFalse();
    }

    public function test_user_can_delete_company_with_access(): void
    {
        expect($this->user->can('delete', $this->company))->toBeTrue();
    }

    public function test_user_cannot_delete_company_without_access(): void
    {
        $otherCompany = Company::factory()->create();

        expect($this->user->can('delete', $otherCompany))->toBeFalse();
    }

    public function test_user_cannot_delete_main_company(): void
    {
        $mainCompany = Company::factory()->create(['name' => 'Main']);

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $mainCompany->id,
            'accessible_type' => Company::class,
        ]);

        expect($this->user->can('delete', $mainCompany))->toBeFalse();
    }

    public function test_user_can_restore_company_with_access(): void
    {
        expect($this->user->can('restore', $this->company))->toBeTrue();
    }

    public function test_user_cannot_restore_company_without_access(): void
    {
        $otherCompany = Company::factory()->create();

        expect($this->user->can('restore', $otherCompany))->toBeFalse();
    }

    public function test_user_can_force_delete_company_with_access(): void
    {
        expect($this->user->can('forceDelete', $this->company))->toBeTrue();
    }

    public function test_user_cannot_force_delete_company_without_access(): void
    {
        $otherCompany = Company::factory()->create();

        expect($this->user->can('forceDelete', $otherCompany))->toBeFalse();
    }

    public function test_user_cannot_force_delete_main_company(): void
    {
        $mainCompany = Company::factory()->create(['name' => 'Main']);

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $mainCompany->id,
            'accessible_type' => Company::class,
        ]);

        expect($this->user->can('forceDelete', $mainCompany))->toBeFalse();
    }
}
