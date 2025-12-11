<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => 'Your email address is not verified.',
                'error' => 'email_not_verified',
            ], 403);
        }

        if ($user->deleted_at !== null) {
            return response()->json([
                'message' => 'Your account has been deactivated.',
                'error' => 'account_deactivated',
            ], 403);
        }

        return $next($request);
    }
}
