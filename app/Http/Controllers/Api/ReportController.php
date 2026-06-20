<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    private function authorizeReports(Request $request): void
    {
        abort_if(
            ! $request->user()->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST]),
            403
        );
    }

    private function dateFilters(Request $request): array
    {
        $request->validate([
            'date_from'   => ['sometimes', 'date'],
            'date_to'     => ['sometimes', 'date', 'after_or_equal:date_from'],
            'provider_id' => ['sometimes', 'integer', 'exists:providers,id'],
            'category'    => ['sometimes', 'string'],
        ]);

        return $request->only(['date_from', 'date_to', 'provider_id', 'category']);
    }

    public function appointments(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        return response()->json([
            'data' => $this->reports->appointmentsReport($this->dateFilters($request)),
        ]);
    }

    public function production(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        return response()->json([
            'data' => $this->reports->productionReport($this->dateFilters($request)),
        ]);
    }

    public function collections(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        return response()->json([
            'data' => $this->reports->collectionsReport($this->dateFilters($request)),
        ]);
    }

    public function recallPerformance(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        return response()->json([
            'data' => $this->reports->recallPerformanceReport($this->dateFilters($request)),
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        return response()->json([
            'data' => $this->reports->inventoryReport($this->dateFilters($request)),
        ]);
    }
}
