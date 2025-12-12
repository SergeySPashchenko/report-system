<?php

declare(strict_types=1);

namespace App\Policies;

final class ExpensetypePolicy
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
        // Всі авторизовані користувачі можуть переглядати expensetypes
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Всі авторизовані користувачі можуть створювати expensetypes
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        // Всі авторизовані користувачі можуть оновлювати expensetypes
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        // Всі авторизовані користувачі можуть видаляти expensetypes
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(): bool
    {
        // Всі авторизовані користувачі можуть відновлювати expensetypes
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(): bool
    {
        // Всі авторизовані користувачі можуть остаточно видаляти expensetypes
        return true;
    }
}
