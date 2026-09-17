<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicClosureRequest;
use App\Models\ClinicClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ClinicClosureController
 *
 * Routes:
 *   GET    /api/v1/closures      → index   (all authenticated)
 *   POST   /api/v1/closures      → store   (owner only)
 *   DELETE /api/v1/closures/{id} → destroy (owner only)
 */
class ClinicClosureController extends Controller
{
    /**
     * List upcoming closures (today onwards by default).
     * Optional ?from=Y-m-d&to=Y-m-d for a specific range.
     */
    public function index(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->toDateString());
        $to   = $request->query('to',   now()->addYear()->toDateString());

        $closures = ClinicClosure::query()
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get()
            ->map(fn ($c) => $this->format($c));

        return response()->json(['data' => $closures]);
    }

    /**
     * Create a new closure. Owner only.
     */
    public function store(StoreClinicClosureRequest $request): JsonResponse
    {
        $closure = ClinicClosure::create(array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));

        return response()->json([
            'message' => 'Clinic closure created.',
            'data'    => $this->format($closure),
        ], 201);
    }

    /**
     * Delete a closure. Owner only.
     */
    public function destroy(ClinicClosure $closure): JsonResponse
    {
        $closure->delete();

        return response()->json(['message' => 'Clinic closure removed.']);
    }

    private function format(ClinicClosure $c): array
    {
        return [
            'id'         => $c->id,
            'date'       => $c->date->toDateString(),
            'reason'     => $c->reason,
            'all_day'    => $c->all_day,
            'start_time' => $c->all_day ? null : $c->start_time,
            'end_time'   => $c->all_day ? null : $c->end_time,
            'created_by' => $c->created_by,
            'created_at' => $c->created_at->toIso8601String(),
        ];
    }
}
