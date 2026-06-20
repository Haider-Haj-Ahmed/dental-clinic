<?php

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

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
        //
    })
    ->booted(function () {
        // ── Named rate limiters per role ──────────────────────────────────────
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

        // Strict limiter for auth endpoints
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    })
    ->create();
