<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var array<string, string> $data */
        $data = $request->validated();
        $user = User::query()->create([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => Hash::make((string) $data['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var array<string, string> $data */
        $data = $request->validated();
        $user = User::query()->where('email', (string) $data['email'])->first();

        if (! $user || ! Hash::check((string) $data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
