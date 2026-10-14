<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Patient portal appointment endpoints.
 * GET /api/v1/portal/appointments          — upcoming + past
 * GET /api/v1/portal/appointments/upcoming — next 5 upcoming
 */
class PortalAppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user();

        $appointments = $patient->appointments()
            ->with(['provider', 'appointmentType', 'operatory'])
            ->orderBy('start_at', 'desc')
            ->paginate($this->perPage($request, 10))
            ->withQueryString();

        return response()->json([
            'data' => $appointments->map(fn ($a) => [
                'id'           => $a->id,
                'start_at'     => $a->start_at->toIso8601String(),
                'end_at'       => $a->end_at->toIso8601String(),
                'status'       => $a->status,
                'type'         => $a->appointmentType?->name,
                'provider'     => $a->provider?->name,
                'operatory'    => $a->operatory?->name,
                'notes'        => $a->notes,
            ]),
            'total'        => $appointments->total(),
            'current_page' => $appointments->currentPage(),
            'last_page'    => $appointments->lastPage(),
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $patient = $request->user();

        $appointments = $patient->appointments()
            ->with(['provider', 'appointmentType'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('start_at', '>', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => $appointments->map(fn ($a) => [
                'id'        => $a->id,
                'start_at'  => $a->start_at->toIso8601String(),
                'end_at'    => $a->end_at->toIso8601String(),
                'status'    => $a->status,
                'type'      => $a->appointmentType?->name,
                'provider'  => $a->provider?->name,
            ]),
        ]);
    }
}
