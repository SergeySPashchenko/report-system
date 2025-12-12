<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandCollection;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService
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

        $brands = $this->brandService->getPaginatedBrands(
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
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $stats = $this->brandService->getStatistics();

        return response()->json($stats);
    }
}
