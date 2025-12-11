<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
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
        // Користувач може переглядати свій профіль або будь-який інший
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Тільки адміністратори можуть створювати користувачів
        // return $user->isAdmin();
        // АБО дозволити всім (для реєстрації)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Користувач може редагувати тільки свій профіль
        // АБО адміністратор може редагувати будь-який профіль
        return $user->id === $model->id;
        // return $user->id === $model->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Користувач не може видалити сам себе
        // Тільки адміністратори можуть видаляти користувачів
        return $user->id !== $model->id;
        // return $user->id !== $model->id && $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Користувач може відновлювати видалених користувачів (крім себе)
        // Тільки адміністратори можуть відновлювати користувачів
        return $user->id !== $model->id;
        // return $user->id !== $model->id && $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Користувач може остаточно видаляти користувачів (крім себе)
        // Тільки адміністратори можуть остаточно видаляти користувачів
        return $user->id !== $model->id;
        // return $user->id !== $model->id && $user->isAdmin();
    }
}
