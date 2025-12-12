<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// ============================================
// Public Auth Routes (no authentication required)
// Rate limited: 5 requests per minute per IP
// ============================================
Route::prefix('v1/auth')->middleware('throttle:auth')->name('auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

// ============================================
// Protected Auth Routes (authentication required)
// ============================================
Route::prefix('v1/auth')->middleware(['auth:sanctum', EnsureUserIsActive::class])->name('auth.')->group(function (): void {
    Route::get('me', [AuthController::class, 'me'])->name('me');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
});

// ============================================
// Protected API Routes
// Rate limited: 60 requests per minute for authenticated users
// EnsureUserIsActive: Only verified and active users can access
// ============================================
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api', EnsureUserIsActive::class])->group(function (): void {
    // Users API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('statistics', [UserController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [UserController::class, 'forceDelete'])->name('force-delete');
    });

    // Users API Resource
    Route::apiResource('users', UserController::class)->parameters([
        'users' => 'user:username',
    ]);

    // Companies API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('companies')->name('companies.')->group(function (): void {
        Route::get('statistics', [CompanyController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [CompanyController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [CompanyController::class, 'forceDelete'])->name('force-delete');
    });

    // Companies API Resource
    Route::apiResource('companies', CompanyController::class)->parameters([
        'companies' => 'company:slug',
    ]);
});
