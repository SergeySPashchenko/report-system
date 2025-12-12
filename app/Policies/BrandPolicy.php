<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

final class BrandPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        // Всі авторизовані користувачі можуть переглядати список
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        // Користувач може переглядати бренди
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Всі авторизовані користувачі можуть створювати бренди
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Brand $brand): bool
    {
        // Користувач може редагувати бренди до яких має доступ
        return $user->brands()->where('brands.id', $brand->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Brand $brand): bool
    {
        // Користувач може видаляти бренди до яких має доступ
        return $user->brands()->where('brands.id', $brand->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Brand $brand): bool
    {
        // Користувач може відновлювати видалені бренди до яких має доступ
        return $user->accesses()
            ->where('accessible_id', $brand->id)
            ->where('accessible_type', 'brand')
            ->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Brand $brand): bool
    {
        // Користувач може остаточно видаляти бренди до яких має доступ
        return $user->brands()->where('brands.id', $brand->id)->exists();
    }
}
