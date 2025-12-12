<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoggedOut;
use Illuminate\Support\Facades\Log;

final class LogUserLogout
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedOut $event): void
    {
        Log::info('User logged out', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
