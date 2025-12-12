<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductItem;
use App\Models\User;
use App\Policies\Concerns\HasAccessCheck;

final class ProductItemPolicy
{
    use HasAccessCheck;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам/брендам має доступ до product items
        return $this->hasAnyProductAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductItem $productItem): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до product items цих продуктів
        // Користувач з доступами по брендам має доступ до product items продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $productItem->product) {
            return false;
        }

        return $this->hasProductAccess($user, $productItem->product);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам/брендам може створювати product items
        return $this->hasAnyProductAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductItem $productItem): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до product items цих продуктів
        // Користувач з доступами по брендам має доступ до product items продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $productItem->product) {
            return false;
        }

        return $this->hasProductAccess($user, $productItem->product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductItem $productItem): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до product items цих продуктів
        // Користувач з доступами по брендам має доступ до product items продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $productItem->product) {
            return false;
        }

        return $this->hasProductAccess($user, $productItem->product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductItem $productItem): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $productItem->product) {
            return false;
        }

        // Перевіряємо через Access напряму, бо видалені product items не повертаються через relationship
        return $this->hasProductAccess($user, $productItem->product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductItem $productItem): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до product items цих продуктів
        // Користувач з доступами по брендам має доступ до product items продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $productItem->product) {
            return false;
        }

        return $this->hasProductAccess($user, $productItem->product);
    }
}
