<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Events\UserRegistered;
use App\Events\UserTokenRefreshed;
use App\Listeners\LogTokenRefresh;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;
use App\Listeners\NotifyAdminOfNewUser;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // User Registration
        UserRegistered::class => [
            SendWelcomeEmail::class,
            NotifyAdminOfNewUser::class,
        ],

        // User Login
        UserLoggedIn::class => [
            LogUserLogin::class,
        ],

        // User Logout
        UserLoggedOut::class => [
            LogUserLogout::class,
        ],

        // Token Refresh
        UserTokenRefreshed::class => [
            LogTokenRefresh::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
