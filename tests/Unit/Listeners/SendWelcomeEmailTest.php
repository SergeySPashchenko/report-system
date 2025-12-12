<?php

declare(strict_types=1);

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('logs new user registration information', function (): void {
    $user = User::factory()->create([
        'email' => 'newuser@example.com',
        'username' => 'newuser',
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('New user registered', Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
            && $context['email'] === 'newuser@example.com'
            && $context['username'] === 'newuser'));

    $event = new UserRegistered(
        user: $user,
        token: 'registration-token'
    );

    $listener = new SendWelcomeEmail();
    $listener->handle($event);
});

test('handles user registration event correctly', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'username' => 'testuser',
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('New user registered', Mockery::any());

    $event = new UserRegistered(
        user: $user,
        token: 'test-token'
    );

    $listener = new SendWelcomeEmail();
    $listener->handle($event);
});
