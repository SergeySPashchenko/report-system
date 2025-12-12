<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->brand = Brand::factory()->create();

        Access::factory()->create([
            'user_id' => $this->user->id,
            'accessible_id' => $this->brand->id,
            'accessible_type' => 'brand',
        ]);
    }

    public function test_user_can_view_any_brands(): void
    {
        expect($this->user->can('viewAny', Brand::class))->toBeTrue();
    }

    public function test_user_can_view_brand(): void
    {
        expect($this->user->can('view', $this->brand))->toBeTrue();
    }

    public function test_user_can_create_brand(): void
    {
        expect($this->user->can('create', Brand::class))->toBeTrue();
    }

    public function test_user_can_update_brand_with_access(): void
    {
        expect($this->user->can('update', $this->brand))->toBeTrue();
    }

    public function test_user_cannot_update_brand_without_access(): void
    {
        $otherBrand = Brand::factory()->create();

        expect($this->user->can('update', $otherBrand))->toBeFalse();
    }

    public function test_user_can_delete_brand_with_access(): void
    {
        expect($this->user->can('delete', $this->brand))->toBeTrue();
    }

    public function test_user_cannot_delete_brand_without_access(): void
    {
        $otherBrand = Brand::factory()->create();

        expect($this->user->can('delete', $otherBrand))->toBeFalse();
    }

    public function test_user_can_restore_brand_with_access(): void
    {
        expect($this->user->can('restore', $this->brand))->toBeTrue();
    }

    public function test_user_cannot_restore_brand_without_access(): void
    {
        $otherBrand = Brand::factory()->create();

        expect($this->user->can('restore', $otherBrand))->toBeFalse();
    }

    public function test_user_can_force_delete_brand_with_access(): void
    {
        expect($this->user->can('forceDelete', $this->brand))->toBeTrue();
    }

    public function test_user_cannot_force_delete_brand_without_access(): void
    {
        $otherBrand = Brand::factory()->create();

        expect($this->user->can('forceDelete', $otherBrand))->toBeFalse();
    }
}
