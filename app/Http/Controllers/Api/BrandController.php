<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandProductRequest;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandProductRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandCollection;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use App\Services\BrandService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): BrandCollection
    {
        $this->authorize('viewAny', Brand::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        /** @var User $user */
        $user = $request->user();

        $brands = $this->brandService->getPaginatedBrands(
            user: $user,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new BrandCollection($brands);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request): BrandResource
    {
        $this->authorize('create', Brand::class);
        $brand = $this->brandService->create($request->validated());

        return new BrandResource($brand);
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand): BrandResource
    {
        $this->authorize('view', $brand);

        return new BrandResource($brand);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        $this->authorize('update', $brand);
        $brand = $this->brandService->update($brand, $request->validated());

        return new BrandResource($brand);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);
        $this->brandService->delete($brand);

        return response()->json([
            'message' => 'Brand deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): BrandResource
    {
        $brand = Brand::withTrashed()->findOrFail($id);

        $this->authorize('restore', $brand);

        $brand = $this->brandService->restore($id);

        return new BrandResource($brand);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $brand = Brand::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $brand);

        $this->brandService->forceDelete($id);

        return response()->json([
            'message' => 'Brand permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get brand statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        /** @var User $user */
        $user = $request->user();

        $stats = $this->brandService->getStatistics($user);

        return response()->json($stats);
    }

    /**
     * Get all products for the brand.
     */
    public function products(Request $request, Brand $brand): ProductCollection
    {
        $this->authorize('view', $brand);

        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        /** @var User $user */
        $user = $request->user();

        $products = $this->productService->getPaginatedProductsForBrand(
            user: $user,
            brand: $brand,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductCollection($products);
    }

    /**
     * Get a specific product for the brand.
     */
    public function product(Request $request, Brand $brand, Product $product): ProductResource
    {
        $this->authorize('view', $brand);
        $this->authorize('view', $product);

        // Перевіряємо що продукт належить бренду
        abort_if($product->brand_id !== $brand->id, 404, 'Product not found for this brand');

        return new ProductResource($product);
    }

    /**
     * Store a newly created product for the brand.
     */
    public function storeProduct(StoreBrandProductRequest $request, Brand $brand): ProductResource
    {
        $this->authorize('view', $brand);
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $data['brand_id'] = $brand->id; // Встановлюємо brand_id з URL

        $product = $this->productService->create($data);

        return new ProductResource($product);
    }

    /**
     * Update the specified product for the brand.
     */
    public function updateProduct(UpdateBrandProductRequest $request, Brand $brand, Product $product): ProductResource
    {
        $this->authorize('view', $brand);
        $this->authorize('update', $product);

        // Перевіряємо що продукт належить бренду
        abort_if($product->brand_id !== $brand->id, 404, 'Product not found for this brand');

        $data = $request->validated();
        $data['brand_id'] = $brand->id; // Встановлюємо brand_id з URL (не можна змінити через nested route)

        $product = $this->productService->update($product, $data);

        return new ProductResource($product);
    }

    /**
     * Remove the specified product for the brand.
     */
    public function destroyProduct(Brand $brand, Product $product): JsonResponse
    {
        $this->authorize('view', $brand);
        $this->authorize('delete', $product);

        // Перевіряємо що продукт належить бренду
        abort_if($product->brand_id !== $brand->id, 404, 'Product not found for this brand');

        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
