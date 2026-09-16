<?php

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities'     => CheckAbilities::class,
            'ability'       => CheckForAnyAbility::class,
            'token.ability' => \App\Http\Middleware\EnforceTokenAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        |──────────────────────────────────────────────────────────────
        | Global JSON exception handler for API routes
        | Ensures all API errors return JSON regardless of Accept header.
        | Never exposes stack traces in production.
        */

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Method not allowed.'], 405);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message'     => 'Too many requests.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                ], 429);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json(['message' => 'Server error. Please try again later.'], 500);
            }
        });
    })
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
    ])
    ->booted(function () {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(30)->by($request->ip());
            }

            return match ($user->role) {
                User::ROLE_OWNER        => Limit::none(),
                User::ROLE_RECEPTIONIST => Limit::perMinute(200)->by($user->id),
                User::ROLE_PROVIDER     => Limit::perMinute(120)->by($user->id),
                User::ROLE_ASSISTANT    => Limit::perMinute(60)->by($user->id),
                default                 => Limit::perMinute(60)->by($user->id),
            };
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    })
    ->create();
