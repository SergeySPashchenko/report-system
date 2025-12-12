<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

final class NotifyAdminOfNewUser
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        Log::info('Notifying admin of new user registration', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // TODO: Send notification to admin
        // Notification::route('mail', config('app.admin_email'))
        //     ->notify(new NewUserRegistered($event->user));
    }
}
