<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\Brand;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;

trait HasAccessCheck
{
    /**
     * Check if user has company access (access to everything).
     */
    protected function hasCompanyAccess(User $user): bool
    {
        return $user->company() instanceof Company;
    }

    /**
     * Check if user has access to brand.
     */
    protected function hasBrandAccess(User $user, Brand $brand): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        // Користувач з доступами по брендам має доступ до всього по брендах
        if ($user->brands()->exists()) {
            return $user->brands()->where('id', $brand->id)->exists();
        }

        return false;
    }

    /**
     * Check if user has access to product.
     */
    protected function hasProductAccess(User $user, Product $product): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        // Користувач з доступами по продуктам має доступ до всього по продуктах
        if ($user->products()->exists()) {
            return $user->products()->where('id', $product->id)->exists();
        }

        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        if ($user->brands()->exists() && $product->brand_id) {
            return $user->brands()->where('id', $product->brand_id)->exists();
        }

        return false;
    }

    /**
     * Check if user has access to any brand (for listing).
     */
    protected function hasAnyBrandAccess(User $user): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        // Користувач з доступами по брендам має доступ до всього по брендах
        return $user->brands()->exists();
    }

    /**
     * Check if user has access to any product (for listing).
     */
    protected function hasAnyProductAccess(User $user): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        // Користувач з доступами по продуктам має доступ до всього по продуктах
        if ($user->products()->exists()) {
            return true;
        }

        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        return $user->brands()->exists();
    }
}
