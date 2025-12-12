<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenderProductRequest;
use App\Http\Requests\StoreGenderRequest;
use App\Http\Requests\UpdateGenderProductRequest;
use App\Http\Requests\UpdateGenderRequest;
use App\Http\Resources\GenderCollection;
use App\Http\Resources\GenderResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use App\Services\GenderService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class GenderController extends Controller
{
    public function __construct(
        private readonly GenderService $genderService,
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): GenderCollection
    {
        $this->authorize('viewAny', Gender::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        $genders = $this->genderService->getPaginatedGenders(
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new GenderCollection($genders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGenderRequest $request): GenderResource
    {
        $this->authorize('create', Gender::class);
        $gender = $this->genderService->create($request->validated());

        return new GenderResource($gender);
    }

    /**
     * Display the specified resource.
     */
    public function show(Gender $gender): GenderResource
    {
        $this->authorize('view', $gender);

        return new GenderResource($gender);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGenderRequest $request, Gender $gender): GenderResource
    {
        $this->authorize('update', $gender);
        $gender = $this->genderService->update($gender, $request->validated());

        return new GenderResource($gender);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gender $gender): JsonResponse
    {
        $this->authorize('delete', $gender);
        $this->genderService->delete($gender);

        return response()->json([
            'message' => 'Gender deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): GenderResource
    {
        $gender = Gender::withTrashed()->findOrFail($id);

        $this->authorize('restore', $gender);

        $gender = $this->genderService->restore($id);

        return new GenderResource($gender);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $gender = Gender::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $gender);

        $this->genderService->forceDelete($id);

        return response()->json([
            'message' => 'Gender permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get gender statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Gender::class);

        $stats = $this->genderService->getStatistics();

        return response()->json($stats);
    }

    /**
     * Get all products for the gender.
     */
    public function products(Request $request, Gender $gender): ProductCollection
    {
        $this->authorize('view', $gender);

        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        /** @var User $user */
        $user = $request->user();

        $products = $this->productService->getPaginatedProductsForGender(
            user: $user,
            gender: $gender,
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ProductCollection($products);
    }

    /**
     * Get a specific product for the gender.
     */
    public function product(Request $request, Gender $gender, Product $product): ProductResource
    {
        $this->authorize('view', $gender);
        $this->authorize('view', $product);

        // Перевіряємо що продукт належить гендеру
        abort_if($product->gender_id !== $gender->id, 404, 'Product not found for this gender');

        return new ProductResource($product);
    }

    /**
     * Store a newly created product for the gender.
     */
    public function storeProduct(StoreGenderProductRequest $request, Gender $gender): ProductResource
    {
        $this->authorize('view', $gender);
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $data['gender_id'] = $gender->id; // Встановлюємо gender_id з URL

        $product = $this->productService->create($data);

        return new ProductResource($product);
    }

    /**
     * Update the specified product for the gender.
     */
    public function updateProduct(UpdateGenderProductRequest $request, Gender $gender, Product $product): ProductResource
    {
        $this->authorize('view', $gender);
        $this->authorize('update', $product);

        // Перевіряємо що продукт належить гендеру
        abort_if($product->gender_id !== $gender->id, 404, 'Product not found for this gender');

        $data = $request->validated();
        $data['gender_id'] = $gender->id; // Встановлюємо gender_id з URL (не можна змінити через nested route)

        $product = $this->productService->update($product, $data);

        return new ProductResource($product);
    }

    /**
     * Remove the specified product for the gender.
     */
    public function destroyProduct(Gender $gender, Product $product): JsonResponse
    {
        $this->authorize('view', $gender);
        $this->authorize('delete', $product);

        // Перевіряємо що продукт належить гендеру
        abort_if($product->gender_id !== $gender->id, 404, 'Product not found for this gender');

        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
