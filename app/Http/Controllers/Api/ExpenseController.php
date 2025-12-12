<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\StoreProductExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Requests\UpdateProductExpenseRequest;
use App\Http\Resources\ExpenseCollection;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ExpenseCollection
    {
        $this->authorize('viewAny', Expense::class);

        /** @var User $user */
        $user = $request->user();

        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $productId = $request->input('product_id');
        $expensetypeId = $request->input('expensetype_id');
        $perPage = $request->input('per_page');

        $expenses = $this->expenseService->getPaginatedExpenses(
            user: $user,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            startDate: is_string($startDate) && $startDate !== '' ? $startDate : null,
            endDate: is_string($endDate) && $endDate !== '' ? $endDate : null,
            productId: is_numeric($productId) ? (int) $productId : null,
            expensetypeId: is_numeric($expensetypeId) ? (int) $expensetypeId : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ExpenseCollection($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): ExpenseResource
    {
        $this->authorize('create', Expense::class);
        $expense = $this->expenseService->create($request->validated());

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $this->authorize('update', $expense);
        $expense = $this->expenseService->update($expense, $request->validated());

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);
        $this->expenseService->delete($expense);

        return response()->json([
            'message' => 'Expense deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get expense statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        /** @var User $user */
        $user = $request->user();

        $stats = $this->expenseService->getStatistics($user);

        return response()->json($stats);
    }

    /**
     * Restore a soft deleted expense.
     */
    public function restore(string $id): ExpenseResource
    {
        $expense = Expense::withTrashed()->findOrFail($id);
        $this->authorize('restore', $expense);

        $expense = $this->expenseService->restore($id);

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Permanently delete an expense.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $expense = Expense::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $expense);

        $this->expenseService->forceDelete($id);

        return response()->json([
            'message' => 'Expense permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get all expenses for a specific product.
     */
    public function products(Request $request, Product $product): ExpenseCollection
    {
        $this->authorize('view', $product);

        /** @var User $user */
        $user = $request->user();

        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page');

        $expenses = $this->expenseService->getPaginatedExpensesForProduct(
            user: $user,
            productId: $product->ProductID,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            startDate: is_string($startDate) && $startDate !== '' ? $startDate : null,
            endDate: is_string($endDate) && $endDate !== '' ? $endDate : null,
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new ExpenseCollection($expenses);
    }

    /**
     * Get a specific expense for the product.
     */
    public function product(Request $request, Product $product, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $product);
        $this->authorize('view', $expense);

        // Перевіряємо що expense належить продукту
        abort_if($expense->ProductID !== $product->ProductID, 404, 'Expense not found for this product');

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Store a newly created expense for the product.
     */
    public function storeProduct(StoreProductExpenseRequest $request, Product $product): ExpenseResource
    {
        $this->authorize('view', $product);
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $data['ProductID'] = $product->ProductID; // Встановлюємо з URL

        $expense = $this->expenseService->create($data);

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Update the specified expense for the product.
     */
    public function updateProduct(UpdateProductExpenseRequest $request, Product $product, Expense $expense): ExpenseResource
    {
        $this->authorize('view', $product);
        $this->authorize('update', $expense);

        // Перевіряємо що expense належить продукту
        abort_if($expense->ProductID !== $product->ProductID, 404, 'Expense not found for this product');

        $data = $request->validated();
        // ProductID не можна змінити через nested route
        unset($data['ProductID']);

        $expense = $this->expenseService->update($expense, $data);

        return new ExpenseResource($expense->load(['product', 'expensetype']));
    }

    /**
     * Remove the specified expense for the product.
     */
    public function destroyProduct(Product $product, Expense $expense): JsonResponse
    {
        $this->authorize('view', $product);
        $this->authorize('delete', $expense);

        // Перевіряємо що expense належить продукту
        abort_if($expense->ProductID !== $product->ProductID, 404, 'Expense not found for this product');

        $this->expenseService->delete($expense);

        return response()->json([
            'message' => 'Expense deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}
