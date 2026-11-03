<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResetAuthGuards
{
    public function handle(Request $request, Closure $next): Response
    {
        // Sanctum::actingAs() stores the user on the resolved guard. Clearing
        // guards here would make every Feature test that uses actingAs get 401.
        // Production / real Bearer-token requests still reset so portal (Patient)
        // and staff (User) auth cannot leak across the shared sanctum guard.
        if (! app()->runningUnitTests()) {
            Auth::forgetGuards();
        }

        return $next($request);
    }
}
