<?php

namespace App\Http\Middleware;

use App\Http\Concerns\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps each route group to the token ability required to access it.
 * Owners always get '*' at login so they bypass every check.
 */
class EnforceTokenAbilities
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Owner tokens carry '*' — always pass
        if ($user->tokenCan('*')) {
            return $next($request);
        }

        if (! $user->tokenCan($ability)) {
            return $this->errorResponse(
                'This action is not authorized for your token scope.',
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
