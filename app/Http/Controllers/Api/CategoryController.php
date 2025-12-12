<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryProductRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryProductRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ExpenseCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductItemCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\ExpenseService;
use App\Services\ProductItemService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductService $productService,
        private readonly ExpenseService $expenseService,
        private readonly ProductItemService $productItemService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CategoryCollection
    {
        $this->authorize('viewAny', Category::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        $categories = $this->categoryService->getPaginatedCategories(
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new CategoryCollection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $this->authorize('create', Category::class);
        $category = $this->categoryService->create($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);
        $category = $this->categoryService->update($category, $request->validated());

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $this->categoryService->delete($category);

        return response()->json([
            'message' => 'Category deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): CategoryResource
    {
        $category = Category::withTrashed()->findOrFail($id);

        $this->authorize('restore', $category);

        $category = $this->categoryService->restore($id);

        return new CategoryResource($category);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $category = Category::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $category);

        $this->categoryService->forceDelete($id);

        return response()->json([
            'message' => 'Category permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get category statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $stats = $this->categoryService->getStatistics();

        return response()->json($stats);
    }

    /**
     * Get all products for the category (both main and marketing).
     */
    public function products(Request $request, Category $category): ProductCollection
    {
        $this->authorize('view', $category);

        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        /** @var User $user */
        $user = $request->user();

        $products = $this->productService->getPaginatedProductsForCategory(
            user: $user,
            category: $category,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductCollection($products);
    }

    /**
     * Get a specific product for the category.
     */
    public function product(Request $request, Category $category, Product $product): ProductResource
    {
        $this->authorize('view', $category);
        $this->authorize('view', $product);

        // Перевіряємо що продукт належить категорії (main або marketing)
        abort_if($product->main_category_id !== $category->id && $product->marketing_category_id !== $category->id, 404, 'Product not found for this category');

        return new ProductResource($product);
    }

    /**
     * Store a newly created product for the category.
     */
    public function storeProduct(StoreCategoryProductRequest $request, Category $category): ProductResource
    {
        $this->authorize('view', $category);
        $this->authorize('create', Product::class);

        // FormRequest автоматично встановлює main_category_id з URL
        $product = $this->productService->create($request->validated());

        return new ProductResource($product);
    }

    /**
     * Update the specified product for the category.
     */
    public function updateProduct(UpdateCategoryProductRequest $request, Category $category, Product $product): ProductResource
    {
        $this->authorize('view', $category);
        $this->authorize('update', $product);

        // Перевіряємо що продукт належить категорії (main або marketing)
        abort_if($product->main_category_id !== $category->id && $product->marketing_category_id !== $category->id, 404, 'Product not found for this category');

        // FormRequest автоматично встановлює main_category_id з URL якщо потрібно
        $product = $this->productService->update($product, $request->validated());

        return new ProductResource($product);
    }

    /**
     * Remove the specified product for the category.
     */
    public function destroyProduct(Category $category, Product $product): JsonResponse
    {
        $this->authorize('view', $category);
        $this->authorize('delete', $product);

        // Перевіряємо що продукт належить категорії (main або marketing)
        abort_if($product->main_category_id !== $category->id && $product->marketing_category_id !== $category->id, 404, 'Product not found for this category');

        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get all expenses for the category.
     */
    public function expenses(Request $request, Category $category): ExpenseCollection
    {
        $this->authorize('view', $category);

        /** @var User $user */
        $user = $request->user();

        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page');

        $expenses = $this->expenseService->getPaginatedExpensesForCategory(
            user: $user,
            categoryId: (string) $category->id,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            startDate: is_string($startDate) && $startDate !== '' ? $startDate : null,
            endDate: is_string($endDate) && $endDate !== '' ? $endDate : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ExpenseCollection($expenses);
    }

    /**
     * Get all product items for a specific category.
     */
    public function productItems(Request $request, Category $category): ProductItemCollection
    {
        $this->authorize('view', $category);

        /** @var User $user */
        $user = $request->user();

        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $active = $request->input('active');
        $deleted = $request->input('deleted');
        $perPage = $request->input('per_page');

        $productItems = $this->productItemService->getPaginatedProductItemsForCategory(
            user: $user,
            categoryId: (string) $category->id,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            active: $active !== null ? filter_var($active, FILTER_VALIDATE_BOOLEAN) : null,
            deleted: $deleted !== null ? filter_var($deleted, FILTER_VALIDATE_BOOLEAN) : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductItemCollection($productItems);
    }
}
