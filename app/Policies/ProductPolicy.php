<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\HasAccessCheck;

final class ProductPolicy
{
    use HasAccessCheck;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам/брендам має доступ до продуктів
        return $this->hasAnyProductAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до всього по продуктах
        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        return $this->hasProductAccess($user, $product);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам/брендам може створювати продукти
        return $this->hasAnyProductAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до всього по продуктах
        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        return $this->hasProductAccess($user, $product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до всього по продуктах
        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        return $this->hasProductAccess($user, $product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        // Перевіряємо через Access напряму, бо видалені продукти не повертаються через relationship
        return $user->accesses()
            ->where('accessible_id', $product->id)
            ->where('accessible_type', 'product')
            ->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до всього по продуктах
        // Користувач з доступами по брендам має доступ до продуктів цих брендів
        return $this->hasProductAccess($user, $product);
    }
}
