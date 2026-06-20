<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function kpis(Request $request): JsonResponse
    {
        abort_if(
            ! $request->user()->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST]),
            403
        );

        return response()->json([
            'data' => $this->reports->dashboardKpis(),
        ]);
    }
}
