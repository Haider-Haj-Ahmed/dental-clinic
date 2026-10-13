<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Patient portal prescription endpoint.
 * GET /api/v1/portal/prescriptions — list patient's prescriptions
 */
class PortalPrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user();

        $prescriptions = Prescription::query()
            ->with(['provider', 'items'])
            ->where('patient_id', $patient->id)
            ->orderBy('issued_at', 'desc')
            ->paginate($this->perPage($request, 10))
            ->withQueryString();

        return response()->json([
            'data' => $prescriptions->map(fn ($p) => [
                'id'        => $p->id,
                'issued_at' => $p->issued_at?->toDateString(),
                'provider'  => $p->provider?->name,
                'notes'     => $p->notes,
                'items'     => $p->items->map(fn ($item) => [
                    'drug_name'    => $item->drug_name,
                    'dose'         => $item->dose,
                    'frequency'    => $item->frequency,
                    'duration'     => $item->duration,
                    'instructions' => $item->instructions,
                ]),
            ]),
            'total'        => $prescriptions->total(),
            'current_page' => $prescriptions->currentPage(),
            'last_page'    => $prescriptions->lastPage(),
        ]);
    }
}
