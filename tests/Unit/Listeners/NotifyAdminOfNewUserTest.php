<?php

declare(strict_types=1);

use App\Events\UserRegistered;
use App\Listeners\NotifyAdminOfNewUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('logs admin notification for new user registration', function (): void {
    $user = User::factory()->create([
        'email' => 'newuser@example.com',
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Notifying admin of new user registration', Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
            && $context['email'] === 'newuser@example.com'));

    $event = new UserRegistered(
        user: $user,
        token: 'registration-token'
    );

    $listener = new NotifyAdminOfNewUser();
    $listener->handle($event);
});

test('handles user registration event for admin notification', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Notifying admin of new user registration', Mockery::any());

    $event = new UserRegistered(
        user: $user,
        token: 'test-token'
    );

    $listener = new NotifyAdminOfNewUser();
    $listener->handle($event);
});
