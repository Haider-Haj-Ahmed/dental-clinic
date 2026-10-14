<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min($request->integer('per_page', $default), $max);
    }

    /**
     * Resolve a safe sort column from ?sort_by= against an allowlist.
     * Falls back to $default if the requested column is not allowed.
     *
     * Usage:
     *   ->orderBy($this->sortBy($request, ['created_at', 'last_name', 'phone'], 'created_at'))
     */
    protected function sortBy(Request $request, array $allowed, string $default): string
    {
        $requested = (string) $request->string('sort_by');
        return in_array($requested, $allowed, true) ? $requested : $default;
    }

    /**
     * Resolve sort direction from ?sort_dir=asc|desc.
     * Defaults to 'desc'. Rejects any other value.
     */
    protected function sortDir(Request $request, string $default = 'desc'): string
    {
        $dir = strtolower((string) $request->string('sort_dir', $default));
        return in_array($dir, ['asc', 'desc'], true) ? $dir : $default;
    }
}
