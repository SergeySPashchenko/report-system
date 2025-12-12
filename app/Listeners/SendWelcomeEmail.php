<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

final class SendWelcomeEmail
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        Log::info('New user registered', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'username' => $event->user->username,
        ]);

        // TODO: Send welcome email
        // Mail::to($event->user)->send(new WelcomeEmail($event->user));
    }
}
