<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserRestored;
use App\Events\UserUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        Log::info('Creating user', ['email' => $user->email]);
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Log::info('User created', [
            'id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
        ]);

        event(new UserCreated($user));
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        Log::info('Updating user', [
            'id' => $user->id,
            'changes' => $user->getDirty(),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        Log::info('User updated', [
            'id' => $user->id,
            'changes' => $user->getChanges(),
        ]);

        event(new UserUpdated($user));
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        Log::warning('Deleting user', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Log::warning('User deleted', [
            'id' => $user->id,
            'email' => $user->email,
        ]);

        // Revoke all tokens when user is soft deleted
        $user->tokens()->delete();

        event(new UserDeleted($user));
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        Log::info('User restored', [
            'id' => $user->id,
            'email' => $user->email,
        ]);

        event(new UserRestored($user));
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        Log::warning('User permanently deleted', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
