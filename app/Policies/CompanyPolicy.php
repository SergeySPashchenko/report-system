<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

final class CompanyPolicy
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
        // Користувач може переглядати компанії
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Всі авторизовані користувачі можуть створювати компанії
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        // Користувач може редагувати компанії до яких має доступ
        return $user->companies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Користувач може видаляти компанії до яких має доступ
        // Але не можна видалити компанію "Main"
        if ($company->name === 'Main') {
            return false;
        }

        return $user->companies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        // Користувач може відновлювати видалені компанії до яких має доступ
        // Перевіряємо через Access напряму, бо видалені компанії не повертаються через relationship
        return $user->accesses()
            ->where('accessible_id', $company->id)
            ->where('accessible_type', Company::class)
            ->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        // Не можна остаточно видаляти компанію "Main"
        if ($company->name === 'Main') {
            return false;
        }

        // Користувач може остаточно видаляти компанії до яких має доступ
        return $user->companies()->where('companies.id', $company->id)->exists();
    }
}
