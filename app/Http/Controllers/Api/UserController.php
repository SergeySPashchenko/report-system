<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): UserCollection
    {
        $this->authorize('viewAny', User::class);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction');
        $perPage = $request->input('per_page');

        $users = $this->userService->getPaginatedUsers(
            search: is_string($search) && $search !== '' ? $search : null,
            sortBy: is_string($sortBy) && $sortBy !== '' ? $sortBy : null,
            sortDirection: is_string($sortDirection) && $sortDirection !== '' ? $sortDirection : 'asc',
            perPage: is_numeric($perPage) ? (int) $perPage : 15
        );

        return new UserCollection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $this->authorize('create', User::class);
        $user = $this->userService->create($request->validated());

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);
        $user = $this->userService->update($user, $request->validated());

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): UserResource
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('restore', $user);

        $user = $this->userService->restore($id);

        return new UserResource($user);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $user);

        $this->userService->forceDelete($id);

        return response()->json([
            'message' => 'User permanently deleted',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $stats = $this->userService->getStatistics();

        return response()->json($stats);
    }
}
