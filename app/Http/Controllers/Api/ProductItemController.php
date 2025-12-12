<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductItemRequest;
use App\Http\Requests\StoreProductProductItemRequest;
use App\Http\Requests\UpdateProductItemRequest;
use App\Http\Requests\UpdateProductProductItemRequest;
use App\Http\Resources\ProductItemCollection;
use App\Http\Resources\ProductItemResource;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use App\Services\ProductItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProductItemController extends Controller
{
    public function __construct(
        private readonly ProductItemService $productItemService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ProductItemCollection
    {
        $this->authorize('viewAny', ProductItem::class);

        /** @var User $user */
        $user = $request->user();

        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $active = $request->input('active');
        $deleted = $request->input('deleted');
        $upSell = $request->input('up_sell');
        $extraProduct = $request->input('extra_product');
        $perPage = $request->input('per_page');

        $productItems = $this->productItemService->getPaginatedProductItems(
            user: $user,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            active: $active !== null ? filter_var($active, FILTER_VALIDATE_BOOLEAN) : null,
            deleted: $deleted !== null ? filter_var($deleted, FILTER_VALIDATE_BOOLEAN) : null,
            upSell: $upSell !== null ? filter_var($upSell, FILTER_VALIDATE_BOOLEAN) : null,
            extraProduct: $extraProduct !== null ? filter_var($extraProduct, FILTER_VALIDATE_BOOLEAN) : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductItemCollection($productItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductItemRequest $request): ProductItemResource
    {
        $this->authorize('create', ProductItem::class);
        $productItem = $this->productItemService->create($request->validated());

        return new ProductItemResource($productItem->load(['product']));
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductItem $productItem): ProductItemResource
    {
        $this->authorize('view', $productItem);

        return new ProductItemResource($productItem->load(['product']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductItemRequest $request, ProductItem $productItem): ProductItemResource
    {
        $this->authorize('update', $productItem);
        $productItem = $this->productItemService->update($productItem, $request->validated());

        return new ProductItemResource($productItem->load(['product']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductItem $productItem): JsonResponse
    {
        $this->authorize('delete', $productItem);
        $this->productItemService->delete($productItem);

        return response()->json([
            'message' => 'Product item deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore a soft deleted product item.
     */
    public function restore(string $id): ProductItemResource
    {
        $productItem = ProductItem::withTrashed()->findOrFail($id);
        $this->authorize('restore', $productItem);

        $restored = $this->productItemService->restore($id);

        return new ProductItemResource($restored->load(['product']));
    }

    /**
     * Permanently delete a product item.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $productItem = ProductItem::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $productItem);

        $this->productItemService->forceDelete($id);

        return response()->json([
            'message' => 'Product item permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get product item statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductItem::class);

        /** @var User $user */
        $user = $request->user();

        $stats = $this->productItemService->getStatistics($user);

        return response()->json($stats);
    }

    /**
     * Get all product items for a specific product.
     */
    public function products(Request $request, Product $product): ProductItemCollection
    {
        $this->authorize('view', $product);

        /** @var User $user */
        $user = $request->user();

        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $active = $request->input('active');
        $deleted = $request->input('deleted');
        $perPage = $request->input('per_page');

        $productItems = $this->productItemService->getPaginatedProductItemsForProduct(
            user: $user,
            productId: $product->ProductID,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            active: $active !== null ? filter_var($active, FILTER_VALIDATE_BOOLEAN) : null,
            deleted: $deleted !== null ? filter_var($deleted, FILTER_VALIDATE_BOOLEAN) : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductItemCollection($productItems);
    }

    /**
     * Get a specific product item for the product.
     */
    public function product(Request $request, Product $product, ProductItem $productItem): ProductItemResource
    {
        $this->authorize('view', $product);
        $this->authorize('view', $productItem);

        // Перевіряємо що product item належить продукту
        abort_if($productItem->ProductID !== $product->ProductID, 404, 'Product item not found for this product');

        return new ProductItemResource($productItem->load(['product']));
    }

    /**
     * Store a newly created product item for the product.
     */
    public function storeProduct(StoreProductProductItemRequest $request, Product $product): ProductItemResource
    {
        $this->authorize('view', $product);
        $this->authorize('create', ProductItem::class);

        $data = $request->validated();
        $data['ProductID'] = $product->ProductID; // Встановлюємо з URL

        $productItem = $this->productItemService->create($data);

        return new ProductItemResource($productItem->load(['product']));
    }

    /**
     * Update the specified product item for the product.
     */
    public function updateProduct(UpdateProductProductItemRequest $request, Product $product, ProductItem $productItem): ProductItemResource
    {
        $this->authorize('view', $product);
        $this->authorize('update', $productItem);

        // Перевіряємо що product item належить продукту
        abort_if($productItem->ProductID !== $product->ProductID, 404, 'Product item not found for this product');

        $data = $request->validated();
        // ProductID не можна змінити через nested route
        unset($data['ProductID']);

        $productItem = $this->productItemService->update($productItem, $data);

        return new ProductItemResource($productItem->load(['product']));
    }

    /**
     * Remove the specified product item for the product.
     */
    public function destroyProduct(Product $product, ProductItem $productItem): JsonResponse
    {
        $this->authorize('view', $product);
        $this->authorize('delete', $productItem);

        // Перевіряємо що product item належить продукту
        abort_if($productItem->ProductID !== $product->ProductID, 404, 'Product item not found for this product');

        $this->productItemService->delete($productItem);

        return response()->json([
            'message' => 'Product item deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
