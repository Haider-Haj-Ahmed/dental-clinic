<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IpAllowlist
 *
 * Blocks requests from IPs not in the allowlist.
 * Only active when IP_ALLOWLIST is set in .env.
 *
 * Usage in .env:
 *   IP_ALLOWLIST=192.168.1.0/24,10.0.0.1,203.0.113.5
 *
 * Supports:
 *   - Exact IPs:    203.0.113.5
 *   - CIDR ranges:  192.168.1.0/24
 *
 * Applied per route group or globally via bootstrap/app.php.
 * When IP_ALLOWLIST is empty or not set, all IPs are allowed.
 */
class IpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = config('auth.ip_allowlist', []);

        if (empty($allowlist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        foreach ($allowlist as $allowed) {
            if ($this->ipMatches($clientIp, trim($allowed))) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Access denied from your IP address.',
        ], 403);
    }

    private function ipMatches(string $clientIp, string $allowed): bool
    {
        // Exact match
        if ($clientIp === $allowed) {
            return true;
        }

        // CIDR range match
        if (str_contains($allowed, '/')) {
            [$subnet, $bits] = explode('/', $allowed, 2);
            $bits = (int) $bits;

            $clientLong  = ip2long($clientIp);
            $subnetLong  = ip2long($subnet);

            if ($clientLong === false || $subnetLong === false) {
                return false;
            }

            $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

            return ($clientLong & $mask) === ($subnetLong & $mask);
        }

        return false;
    }
}
