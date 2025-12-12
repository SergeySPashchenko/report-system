<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExpensetypeController;
use App\Http\Controllers\Api\GenderController;
use App\Http\Controllers\Api\ProductController;
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

    // Brands API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('brands')->name('brands.')->group(function (): void {
        Route::get('statistics', [BrandController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [BrandController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [BrandController::class, 'forceDelete'])->name('force-delete');
        // Nested products routes
        Route::get('{brand}/products', [BrandController::class, 'products'])->name('products.index');
        Route::post('{brand}/products', [BrandController::class, 'storeProduct'])->name('products.store');
        Route::get('{brand}/products/{product}', [BrandController::class, 'product'])->name('products.show');
        Route::put('{brand}/products/{product}', [BrandController::class, 'updateProduct'])->name('products.update');
        Route::patch('{brand}/products/{product}', [BrandController::class, 'updateProduct'])->name('products.update');
        Route::delete('{brand}/products/{product}', [BrandController::class, 'destroyProduct'])->name('products.destroy');
        // Nested expenses routes
        Route::get('{brand}/expenses', [BrandController::class, 'expenses'])->name('expenses.index');
    });

    // Brands API Resource
    Route::apiResource('brands', BrandController::class)->parameters([
        'brands' => 'brand:slug',
    ]);

    // Products API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('products')->name('products.')->group(function (): void {
        Route::get('statistics', [ProductController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [ProductController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [ProductController::class, 'forceDelete'])->name('force-delete');
    });

    // Products API Resource
    Route::apiResource('products', ProductController::class)->parameters([
        'products' => 'product:slug',
    ]);

    // Categories API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('categories')->name('categories.')->group(function (): void {
        Route::get('statistics', [CategoryController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [CategoryController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [CategoryController::class, 'forceDelete'])->name('force-delete');
        // Nested products routes
        Route::get('{category}/products', [CategoryController::class, 'products'])->name('products.index');
        Route::post('{category}/products', [CategoryController::class, 'storeProduct'])->name('products.store');
        Route::get('{category}/products/{product}', [CategoryController::class, 'product'])->name('products.show');
        Route::put('{category}/products/{product}', [CategoryController::class, 'updateProduct'])->name('products.update');
        Route::patch('{category}/products/{product}', [CategoryController::class, 'updateProduct'])->name('products.update');
        Route::delete('{category}/products/{product}', [CategoryController::class, 'destroyProduct'])->name('products.destroy');
        // Nested expenses routes
        Route::get('{category}/expenses', [CategoryController::class, 'expenses'])->name('expenses.index');
    });

    // Categories API Resource
    Route::apiResource('categories', CategoryController::class)->parameters([
        'categories' => 'category:slug',
    ]);

    // Genders API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('genders')->name('genders.')->group(function (): void {
        Route::get('statistics', [GenderController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [GenderController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [GenderController::class, 'forceDelete'])->name('force-delete');
        // Nested products routes
        Route::get('{gender}/products', [GenderController::class, 'products'])->name('products.index');
        Route::post('{gender}/products', [GenderController::class, 'storeProduct'])->name('products.store');
        Route::get('{gender}/products/{product}', [GenderController::class, 'product'])->name('products.show');
        Route::put('{gender}/products/{product}', [GenderController::class, 'updateProduct'])->name('products.update');
        Route::patch('{gender}/products/{product}', [GenderController::class, 'updateProduct'])->name('products.update');
        Route::delete('{gender}/products/{product}', [GenderController::class, 'destroyProduct'])->name('products.destroy');
        // Nested expenses routes
        Route::get('{gender}/expenses', [GenderController::class, 'expenses'])->name('expenses.index');
    });

    // Genders API Resource
    Route::apiResource('genders', GenderController::class)->parameters([
        'genders' => 'gender:slug',
    ]);

    // Expensetypes API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('expensetypes')->name('expensetypes.')->group(function (): void {
        Route::get('statistics', [ExpensetypeController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [ExpensetypeController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [ExpensetypeController::class, 'forceDelete'])->name('force-delete');
        // Nested expenses routes
        Route::get('{expensetype}/expenses', [ExpensetypeController::class, 'expenses'])->name('expenses.index');
        Route::post('{expensetype}/expenses', [ExpensetypeController::class, 'storeExpense'])->name('expenses.store');
        Route::get('{expensetype}/expenses/{expense}', [ExpensetypeController::class, 'expense'])->name('expenses.show');
        Route::put('{expensetype}/expenses/{expense}', [ExpensetypeController::class, 'updateExpense'])->name('expenses.update');
        Route::patch('{expensetype}/expenses/{expense}', [ExpensetypeController::class, 'updateExpense'])->name('expenses.update');
        Route::delete('{expensetype}/expenses/{expense}', [ExpensetypeController::class, 'destroyExpense'])->name('expenses.destroy');
    });

    // Expensetypes API Resource
    Route::apiResource('expensetypes', ExpensetypeController::class)->parameters([
        'expensetypes' => 'expensetype:slug',
    ]);

    // Expenses API - statistics must be before apiResource to avoid route conflicts
    Route::prefix('expenses')->name('expenses.')->group(function (): void {
        Route::get('statistics', [ExpenseController::class, 'statistics'])->name('statistics');
        Route::post('{id}/restore', [ExpenseController::class, 'restore'])->name('restore');
        Route::delete('{id}/force', [ExpenseController::class, 'forceDelete'])->name('force-delete');
    });

    // Expenses API Resource
    Route::apiResource('expenses', ExpenseController::class);

    // Nested expenses routes for products
    Route::prefix('products')->name('products.')->group(function (): void {
        Route::get('{product}/expenses', [ExpenseController::class, 'products'])->name('expenses.index');
        Route::post('{product}/expenses', [ExpenseController::class, 'storeProduct'])->name('expenses.store');
        Route::get('{product}/expenses/{expense}', [ExpenseController::class, 'product'])->name('expenses.show');
        Route::put('{product}/expenses/{expense}', [ExpenseController::class, 'updateProduct'])->name('expenses.update');
        Route::patch('{product}/expenses/{expense}', [ExpenseController::class, 'updateProduct'])->name('expenses.update');
        Route::delete('{product}/expenses/{expense}', [ExpenseController::class, 'destroyProduct'])->name('expenses.destroy');
    });
});
