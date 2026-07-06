<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use ApiResponse, AuthorizesRequests, ValidatesRequests;

    protected function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min($request->integer('per_page', $default), $max);
    }
}
