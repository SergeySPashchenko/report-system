<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\HasAccessCheck;

final class ExpensePolicy
{
    use HasAccessCheck;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам/брендам має доступ до expenses
        return $this->hasAnyProductAccess($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Expense $expense): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до expenses цих продуктів
        // Користувач з доступами по брендам має доступ до expenses продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $expense->product) {
            return false;
        }

        return $this->hasProductAccess($user, $expense->product);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам/брендам може створювати expenses
        return $this->hasAnyProductAccess($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Expense $expense): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до expenses цих продуктів
        // Користувач з доступами по брендам має доступ до expenses продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $expense->product) {
            return false;
        }

        return $this->hasProductAccess($user, $expense->product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Expense $expense): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до expenses цих продуктів
        // Користувач з доступами по брендам має доступ до expenses продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $expense->product) {
            return false;
        }

        return $this->hasProductAccess($user, $expense->product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Expense $expense): bool
    {
        // Користувач компанії має доступ до всього
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $expense->product) {
            return false;
        }

        // Перевіряємо через Access напряму, бо видалені expenses не повертаються через relationship
        return $this->hasProductAccess($user, $expense->product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Expense $expense): bool
    {
        // Користувач компанії має доступ до всього
        // Користувач з доступами по продуктам має доступ до expenses цих продуктів
        // Користувач з доступами по брендам має доступ до expenses продуктів цих брендів
        if ($this->hasCompanyAccess($user)) {
            return true;
        }

        if (! $expense->product) {
            return false;
        }

        return $this->hasProductAccess($user, $expense->product);
    }
}
