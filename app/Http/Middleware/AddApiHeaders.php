<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AddApiHeaders
 *
 * Appends standard API headers to every response in the api middleware group:
 *
 *   X-API-Version:  current application version (from config/app.php)
 *   X-Request-ID:   unique UUID per request for distributed tracing
 *
 * The X-Request-ID is also stored on the request object so it can be
 * referenced in logs:
 *   Log::withContext(['request_id' => $request->header('X-Request-ID')]);
 */
class AddApiHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Use incoming X-Request-ID if provided by client, else generate one
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();

        // Bind to request so controllers/logs can reference it
        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        $response->headers->set('X-API-Version', config('app.version', '3.0.0'));
        $response->headers->set('X-Request-ID',  $requestId);

        return $response;
    }
}
