<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForcePasswordChange
 *
 * Blocks all API requests (except change-password and logout) when the
 * authenticated user's password hasn't been changed within the configured
 * number of days (AUTH_PASSWORD_EXPIRY_DAYS, default 90).
 *
 * Returns 403 with `password_change_required: true` so clients can
 * redirect the user to the change-password screen.
 *
 * Applied selectively — add to routes that need enforcement:
 *   Route::middleware(['auth:sanctum', 'force.password.change'])
 *
 * Exempt routes (always allowed):
 *   POST /auth/change-password
 *   POST /auth/logout
 *   POST /auth/logout-all
 */
class ForcePasswordChange
{
    private const EXEMPT_PATHS = [
        'api/v1/auth/change-password',
        'api/v1/auth/logout',
        'api/v1/auth/logout-all',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $expiryDays = (int) config('auth.password_expiry_days', 0);

        // 0 = disabled (no forced expiry)
        if ($expiryDays === 0) {
            return $next($request);
        }

        // Allow exempt routes through
        foreach (self::EXEMPT_PATHS as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $lastChanged = $user->password_changed_at ?? $user->created_at;

        if ($lastChanged->addDays($expiryDays)->isPast()) {
            return response()->json([
                'message'                  => 'Your password has expired. Please change it to continue.',
                'password_change_required' => true,
                'expired_at'               => $lastChanged->addDays($expiryDays)->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}
