<?php

declare(strict_types=1);

use App\Events\UserTokenRefreshed;
use App\Listeners\LogTokenRefresh;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('logs user token refresh information', function (): void {
    Log::shouldReceive('info')
        ->with('Creating user', Mockery::any())
        ->andReturn(true);
    Log::shouldReceive('info')
        ->with('User created', Mockery::any())
        ->andReturn(true);
    Log::shouldReceive('info')
        ->with(Mockery::anyOf('Main company created for first user', 'Company access assigned to user'), Mockery::any())
        ->zeroOrMoreTimes()
        ->andReturn(true);
    Log::shouldReceive('info')
        ->once()
        ->with('User token refreshed', Mockery::on(fn (array $context): bool => isset($context['user_id'])
            && isset($context['email'])
            && isset($context['timestamp'])));

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $event = new UserTokenRefreshed(
        user: $user,
        newToken: 'new-refreshed-token'
    );

    $listener = new LogTokenRefresh();
    $listener->handle($event);
});

test('includes correct data in token refresh log entry', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    Log::shouldReceive('info')
        ->with(Mockery::anyOf('Main company created for first user', 'Company access assigned to user'), Mockery::any())
        ->zeroOrMoreTimes()
        ->andReturn(true);
    Log::shouldReceive('info')
        ->once()
        ->with('User token refreshed', Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
            && $context['email'] === 'test@example.com'
            && isset($context['timestamp'])
            && is_string($context['timestamp'])));

    $event = new UserTokenRefreshed(
        user: $user,
        newToken: 'new-token'
    );

    $listener = new LogTokenRefresh();
    $listener->handle($event);
});
