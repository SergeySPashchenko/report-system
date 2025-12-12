<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyCollection;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CompanyCollection
    {
        $this->authorize('viewAny', Company::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        $companies = $this->companyService->getPaginatedCompanies(
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new CompanyCollection($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): CompanyResource
    {
        $this->authorize('create', Company::class);
        $company = $this->companyService->create($request->validated());

        return new CompanyResource($company);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): CompanyResource
    {
        $this->authorize('view', $company);

        return new CompanyResource($company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): CompanyResource
    {
        $this->authorize('update', $company);
        $company = $this->companyService->update($company, $request->validated());

        return new CompanyResource($company);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $this->authorize('delete', $company);
        $this->companyService->delete($company);

        return response()->json([
            'message' => 'Company deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): CompanyResource
    {
        $company = Company::withTrashed()->findOrFail($id);

        $this->authorize('restore', $company);

        $company = $this->companyService->restore($id);

        return new CompanyResource($company);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $company = Company::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $company);

        $this->companyService->forceDelete($id);

        return response()->json([
            'message' => 'Company permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get company statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        $stats = $this->companyService->getStatistics();

        return response()->json($stats);
    }
}
