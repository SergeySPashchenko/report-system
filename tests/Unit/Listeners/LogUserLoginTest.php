<?php

declare(strict_types=1);

use App\Events\UserLoggedIn;
use App\Listeners\LogUserLogin;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('logs user login information', function (): void {
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
        ->with('User logged in', Mockery::on(fn (array $context): bool => isset($context['user_id'])
            && isset($context['email'])
            && isset($context['ip_address'])
            && isset($context['user_agent'])
            && isset($context['timestamp'])));

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $event = new UserLoggedIn(
        user: $user,
        token: 'test-token',
        ipAddress: '192.168.1.1',
        userAgent: 'Mozilla/5.0'
    );

    $listener = new LogUserLogin();
    $listener->handle($event);
});

test('includes correct data in login log entry', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    Log::shouldReceive('info')
        ->with(Mockery::anyOf('Main company created for first user', 'Company access assigned to user'), Mockery::any())
        ->zeroOrMoreTimes()
        ->andReturn(true);
    Log::shouldReceive('info')
        ->once()
        ->with('User logged in', Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
            && $context['email'] === 'test@example.com'
            && $context['ip_address'] === '192.168.1.1'
            && $context['user_agent'] === 'Mozilla/5.0'
            && isset($context['timestamp'])
            && is_string($context['timestamp'])));

    $event = new UserLoggedIn(
        user: $user,
        token: 'test-token',
        ipAddress: '192.168.1.1',
        userAgent: 'Mozilla/5.0'
    );

    $listener = new LogUserLogin();
    $listener->handle($event);
});
