<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientMedicalCaseResource;
use App\Http\Resources\PatientMedicalDocumentResource;
use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientMedicalCase;
use App\Models\PatientMedicalDocument;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientTimelineController extends Controller
{
    /**
     * Allowed type filters the caller can pass via ?type[]=
     */
    private const ALLOWED_TYPES = [
        'appointment',
        'medical_case',
        'document',
        'encounter',
        'prescription',
    ];

    public function __invoke(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $request->validate([
            'type'      => ['sometimes', 'array'],
            'type.*'    => ['string', 'in:'.implode(',', self::ALLOWED_TYPES)],
            'date_from' => ['sometimes', 'date'],
            'date_to'   => ['sometimes', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $types    = $request->input('type', self::ALLOWED_TYPES);
        $dateFrom = $request->date('date_from');
        $dateTo   = $request->date('date_to');
        $perPage  = min($request->integer('per_page', 20), 100);

        $items = collect();

        // ── Appointments ─────────────────────────────────────────────────────
        if (in_array('appointment', $types)) {
            $patient->appointments()
                ->with(['provider', 'appointmentType', 'operatory'])
                ->when($dateFrom, fn ($q) => $q->whereDate('start_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('start_at', '<=', $dateTo))
                ->get()
                ->each(function (Appointment $a) use ($items) {
                    $items->push([
                        'type'       => 'appointment',
                        'date'       => $a->start_at,
                        'date_sort'  => $a->start_at->timestamp,
                        'data'       => AppointmentResource::make($a)->resolve(),
                    ]);
                });
        }

        // ── Medical cases ─────────────────────────────────────────────────────
        if (in_array('medical_case', $types)) {
            $patient->medicalCases()
                ->with(['provider', 'creator'])
                ->withCount('documents')
                ->when($dateFrom, fn ($q) => $q->whereDate('case_date', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('case_date', '<=', $dateTo))
                ->get()
                ->each(function (PatientMedicalCase $c) use ($items) {
                    $items->push([
                        'type'      => 'medical_case',
                        'date'      => $c->case_date,
                        'date_sort' => $c->case_date->timestamp,
                        'data'      => PatientMedicalCaseResource::make($c)->resolve(),
                    ]);
                });
        }

        // ── Documents ─────────────────────────────────────────────────────────
        if (in_array('document', $types)) {
            $patient->medicalDocuments()
                ->with(['provider', 'uploadedBy'])
                ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->get()
                ->each(function (PatientMedicalDocument $d) use ($items) {
                    $items->push([
                        'type'      => 'document',
                        'date'      => $d->created_at,
                        'date_sort' => $d->created_at->timestamp,
                        'data'      => PatientMedicalDocumentResource::make($d)->resolve(),
                    ]);
                });
        }

        // ── Encounters ────────────────────────────────────────────────────────
        if (in_array('encounter', $types)) {
            $patient->encounters()
                ->with('provider')
                ->when($dateFrom, fn ($q) => $q->whereDate('encounter_date', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('encounter_date', '<=', $dateTo))
                ->get()
                ->each(function (Encounter $e) use ($items) {
                    $items->push([
                        'type'      => 'encounter',
                        'date'      => $e->encounter_date,
                        'date_sort' => $e->encounter_date->timestamp,
                        'data'      => [
                            'id'             => $e->id,
                            'encounter_date' => $e->encounter_date->toDateString(),
                            'assessment'     => $e->assessment,
                            'is_locked'      => $e->is_locked,
                            'provider'       => $e->provider ? [
                                'id'   => $e->provider->id,
                                'name' => $e->provider->name,
                            ] : null,
                        ],
                    ]);
                });
        }

        // ── Prescriptions ─────────────────────────────────────────────────────
        if (in_array('prescription', $types)) {
            $patient->prescriptions()
                ->with('provider')
                ->when($dateFrom, fn ($q) => $q->whereDate('issued_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('issued_at', '<=', $dateTo))
                ->get()
                ->each(function (Prescription $p) use ($items) {
                    $items->push([
                        'type'      => 'prescription',
                        'date'      => $p->issued_at,
                        'date_sort' => $p->issued_at->timestamp,
                        'data'      => [
                            'id'         => $p->id,
                            'issued_at'  => $p->issued_at->toIso8601String(),
                            'notes'      => $p->notes,
                            'is_printed' => $p->is_printed,
                            'provider'   => $p->provider ? [
                                'id'   => $p->provider->id,
                                'name' => $p->provider->name,
                            ] : null,
                        ],
                    ]);
                });
        }

        // ── Sort descending by date, then paginate manually ───────────────────
        $sorted = $items->sortByDesc('date_sort')->values();

        $page    = max(1, (int) $request->integer('page', 1));
        $total   = $sorted->count();
        $sliced  = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $sliced->map(fn ($item) => [
                'type' => $item['type'],
                'date' => $item['date'] instanceof \Illuminate\Support\Carbon
                    ? $item['date']->toIso8601String()
                    : (string) $item['date'],
                'data' => $item['data'],
            ]),
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
                'types'        => $types,
            ],
        ]);
    }
}
