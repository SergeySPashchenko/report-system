<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Events\UserRegistered;
use App\Events\UserTokenRefreshed;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var array{name: string, email: string, password: string} $validated */
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        event(new UserRegistered($user, $token));

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user and create token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var array{email: string, password: string} $validated */
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Видалити старі токени (опціонально)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        event(new UserLoggedIn(
            $user,
            $token,
            $request->ip() ?? '0.0.0.0',
            $request->userAgent() ?? 'Unknown'
        ));

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): UserResource
    {
        $user = $request->user();

        assert($user instanceof User);

        return new UserResource($user);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $user->currentAccessToken()->delete();

        event(new UserLoggedOut($user, $request->ip() ?? '0.0.0.0'));

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens).
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $user->tokens()->delete();

        event(new UserLoggedOut($user, $request->ip() ?? '0.0.0.0'));

        return response()->json([
            'message' => 'Logged out from all devices successfully',
        ]);
    }

    /**
     * Refresh token (revoke current and create new).
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        // Видалити поточний токен
        $user->currentAccessToken()->delete();

        // Створити новий токен
        $token = $user->createToken('auth_token')->plainTextToken;

        event(new UserTokenRefreshed($user, $token));

        return response()->json([
            'message' => 'Token refreshed successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
