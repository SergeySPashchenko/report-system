<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ProductCollection
    {
        $this->authorize('viewAny', Product::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        /** @var User $user */
        $user = $request->user();

        $products = $this->productService->getPaginatedProducts(
            user: $user,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $this->authorize('create', Product::class);
        $product = $this->productService->create($request->validated());

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);
        $product = $this->productService->update($product, $request->validated());

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): ProductResource
    {
        $product = Product::withTrashed()->findOrFail($id);

        $this->authorize('restore', $product);

        $product = $this->productService->restore($id);

        return new ProductResource($product);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $product);

        $this->productService->forceDelete($id);

        return response()->json([
            'message' => 'Product permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get product statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        /** @var User $user */
        $user = $request->user();

        $stats = $this->productService->getStatistics($user);

        return response()->json($stats);
    }
}
