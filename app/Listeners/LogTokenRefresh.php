<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserTokenRefreshed;
use Illuminate\Support\Facades\Log;

final class LogTokenRefresh
{
    /**
     * Handle the event.
     */
    public function handle(UserTokenRefreshed $event): void
    {
        Log::info('User token refreshed', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
