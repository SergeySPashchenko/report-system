<?php

declare(strict_types=1);

use App\Events\UserLoggedOut;
use App\Listeners\LogUserLogout;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('logs user logout information', function (): void {
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
        ->with('User logged out', Mockery::on(fn (array $context): bool => isset($context['user_id'])
            && isset($context['email'])
            && isset($context['ip_address'])
            && isset($context['timestamp'])));

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $event = new UserLoggedOut(
        user: $user,
        ipAddress: '192.168.1.1'
    );

    $listener = new LogUserLogout();
    $listener->handle($event);
});

test('includes correct data in logout log entry', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    Log::shouldReceive('info')
        ->with(Mockery::anyOf('Main company created for first user', 'Company access assigned to user'), Mockery::any())
        ->zeroOrMoreTimes()
        ->andReturn(true);
    Log::shouldReceive('info')
        ->once()
        ->with('User logged out', Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
            && $context['email'] === 'test@example.com'
            && $context['ip_address'] === '192.168.1.1'
            && isset($context['timestamp'])
            && is_string($context['timestamp'])));

    $event = new UserLoggedOut(
        user: $user,
        ipAddress: '192.168.1.1'
    );

    $listener = new LogUserLogout();
    $listener->handle($event);
});
