<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use App\Policies\Concerns\HasAccessCheck;

final class BrandPolicy
{
    use HasAccessCheck;

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
        // Користувач компанії має доступ до всього
        // Користувач з доступами по брендам має доступ до всього по брендах
        return $this->hasBrandAccess($user, $brand);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Brand $brand): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по брендам має доступ до всього по брендах
        return $this->hasBrandAccess($user, $brand);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Brand $brand): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по брендам має доступ до всього по брендах
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        // Перевіряємо через Access напряму, бо видалені бренди не повертаються через relationship
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
        // Користувач компанії має доступ до всього
        // Користувач з доступами по брендам має доступ до всього по брендах
        return $this->hasBrandAccess($user, $brand);
    }
}
