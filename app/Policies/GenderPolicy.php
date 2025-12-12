<?php

declare(strict_types=1);

namespace App\Policies;

final class GenderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        // Гендери доступні всім авторизованим користувачам
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        // Гендери доступні всім авторизованим користувачам
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Всі авторизовані користувачі можуть створювати гендери
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        // Всі авторизовані користувачі можуть редагувати гендери
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        // Всі авторизовані користувачі можуть видаляти гендери
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(): bool
    {
        // Всі авторизовані користувачі можуть відновлювати гендери
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(): bool
    {
        // Всі авторизовані користувачі можуть остаточно видаляти гендери
        return true;
    }
}
