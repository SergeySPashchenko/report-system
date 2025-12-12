<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpensetypeExpenseRequest;
use App\Http\Requests\StoreExpensetypeRequest;
use App\Http\Requests\UpdateExpensetypeExpenseRequest;
use App\Http\Requests\UpdateExpensetypeRequest;
use App\Http\Resources\ExpenseCollection;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ExpensetypeCollection;
use App\Http\Resources\ExpensetypeResource;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\ExpensetypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ExpensetypeController extends Controller
{
    public function __construct(
        private readonly ExpensetypeService $expensetypeService,
        private readonly ExpenseService $expenseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ExpensetypeCollection
    {
        $this->authorize('viewAny', Expensetype::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        $expensetypes = $this->expensetypeService->getPaginatedExpensetypes(
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ExpensetypeCollection($expensetypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpensetypeRequest $request): ExpensetypeResource
    {
        $this->authorize('create', Expensetype::class);
        $expensetype = $this->expensetypeService->create($request->validated());

        return new ExpensetypeResource($expensetype);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expensetype $expensetype): ExpensetypeResource
    {
        $this->authorize('view', $expensetype);

        return new ExpensetypeResource($expensetype);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpensetypeRequest $request, Expensetype $expensetype): ExpensetypeResource
    {
        $this->authorize('update', $expensetype);
        $expensetype = $this->expensetypeService->update($expensetype, $request->validated());

        return new ExpensetypeResource($expensetype);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expensetype $expensetype): JsonResponse
    {
        $this->authorize('delete', $expensetype);
        $this->expensetypeService->delete($expensetype);

        return response()->json([
            'message' => 'Expensetype deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get expensetype statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Expensetype::class);

        $stats = $this->expensetypeService->getStatistics();

        return response()->json($stats);
    }

    /**
     * Restore a soft deleted expensetype.
     */
    public function restore(string $id): ExpensetypeResource
    {
        $expensetype = Expensetype::withTrashed()->findOrFail($id);
        $this->authorize('restore', $expensetype);

        $expensetype = $this->expensetypeService->restore($id);

        return new ExpensetypeResource($expensetype);
    }

    /**
     * Permanently delete an expensetype.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $expensetype = Expensetype::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $expensetype);

        $this->expensetypeService->forceDelete($id);

        return response()->json([
            'message' => 'Expensetype permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get all expenses for the expensetype.
     */
    public function expenses(Request $request, Expensetype $expensetype): ExpenseCollection
    {
        $this->authorize('view', $expensetype);

        /** @var User $user */
        $user = $request->user();

        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page');

        $expenses = $this->expenseService->getPaginatedExpensesForExpensetype(
            user: $user,
            expensetypeId: $expensetype->ExpenseTypeID,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            startDate: is_string($startDate) && $startDate !== '' ? $startDate : null,
            endDate: is_string($endDate) && $endDate !== '' ? $endDate : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ExpenseCollection($expenses);
    }

    /**
     * Get a specific expense for the expensetype.
     */
    public function expense(Request $request, Expensetype $expensetype, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expensetype);
        $this->authorize('view', $expense);

        // Перевіряємо що expense належить expensetype
        abort_if($expense->ExpenseID !== $expensetype->ExpenseTypeID, 404, 'Expense not found for this expensetype');

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Store a newly created expense for the expensetype.
     */
    public function storeExpense(StoreExpensetypeExpenseRequest $request, Expensetype $expensetype): ExpenseResource
    {
        $this->authorize('view', $expensetype);
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $data['ExpenseID'] = $expensetype->ExpenseTypeID; // Встановлюємо з URL

        $expense = $this->expenseService->create($data);

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Update the specified expense for the expensetype.
     */
    public function updateExpense(UpdateExpensetypeExpenseRequest $request, Expensetype $expensetype, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expensetype);
        $this->authorize('update', $expense);

        // Перевіряємо що expense належить expensetype
        abort_if($expense->ExpenseID !== $expensetype->ExpenseTypeID, 404, 'Expense not found for this expensetype');

        $data = $request->validated();
        // ExpenseID не можна змінити через nested route
        unset($data['ExpenseID']);

        $expense = $this->expenseService->update($expense, $data);

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Remove the specified expense for the expensetype.
     */
    public function destroyExpense(Expensetype $expensetype, Expense $expense): JsonResponse
    {
        $this->authorize('view', $expensetype);
        $this->authorize('delete', $expense);

        // Перевіряємо що expense належить expensetype
        abort_if($expense->ExpenseID !== $expensetype->ExpenseTypeID, 404, 'Expense not found for this expensetype');

        $this->expenseService->delete($expense);

        return response()->json([
            'message' => 'Expense deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
