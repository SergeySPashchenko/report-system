<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Access;
use App\Models\Brand;
use App\Models\Company;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Relation::enforceMorphMap([
            'user' => User::class,
            'company' => Company::class,
            'brand' => Brand::class,
            'access' => Access::class,
        ]);
        User::observe(UserObserver::class);

        // Rate limiting для API
        RateLimiter::for('api', fn (Request $request) => $request->user()
            ? Limit::perMinute(60)->by($request->user()->id)
            : Limit::perMinute(20)->by($request->ip()));

        // Strict rate limiting для auth endpoints
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
