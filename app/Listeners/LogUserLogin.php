<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Support\Facades\Log;

final class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        Log::info('User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // TODO: Save login history to database
        // LoginHistory::create([
        //     'user_id' => $event->user->id,
        //     'ip_address' => $event->ipAddress,
        //     'user_agent' => $event->userAgent,
        //     'logged_in_at' => now(),
        // ]);
    }
}
