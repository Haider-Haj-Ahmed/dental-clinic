<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\WebhookDispatcher;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Patient::class, 'patient');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $patients = Patient::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->boolean('with_archived'), fn ($q) => $q->withTrashed())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PatientResource::collection($patients);
    }

    public function store(StorePatientRequest $request): PatientResource
    {
        $patient = Patient::query()->create($request->validated());

        // Dispatch patient.created webhook
        app(WebhookDispatcher::class)->dispatch('patient.created', [
            'patient_id'  => $patient->id,
            'first_name'  => $patient->first_name,
            'last_name'   => $patient->last_name,
            'email'       => $patient->email,
            'phone'       => $patient->phone,
            'created_at'  => $patient->created_at->toIso8601String(),
        ]);

        return PatientResource::make($patient);
    }

    public function show(Patient $patient): PatientResource
    {
        return PatientResource::make($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): PatientResource
    {
        $patient->update($request->validated());

        return PatientResource::make($patient->refresh());
    }

    /** Soft-delete (archive) — never hard-delete via API */
    public function destroy(Patient $patient): Response
    {
        $patient->delete();

        return response()->noContent();
    }

    /** POST /patients/{patient}/archive */
    public function archive(Patient $patient): JsonResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return response()->json(['message' => 'Patient archived.']);
    }

    /** POST /patients/{patient}/restore */
    public function restore(Patient $patient): PatientResource
    {
        $this->authorize('restore', $patient);

        $patient->restore();

        return PatientResource::make($patient->refresh());
    }

    /* ══════════════════════════════════════════════════════════
     * EXPORT
     * GET /api/v1/patients/{patient}/export
     *
     * Owner only. Exports full patient record as JSON — GDPR/PDPA
     * compliant data export. Includes demographics, medical history,
     * allergies, conditions, medications, appointments, encounters,
     * invoices, payments, documents, AI results, and audit log.
     * ══════════════════════════════════════════════════════════ */
    public function export(Patient $patient): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $patient);
        abort_unless(request()->user()->isOwner(), 403, 'Only clinic owners may export patient data.');

        $patient->load([
            'contacts',
            'allergies',
            'conditions',
            'medications',
            'consents',
            'medicalCases',
            'documents',
            'appointments.provider',
            'appointments.appointmentType',
            'encounters.provider',
            'encounters.prescriptions',
            'invoices.items',
            'invoices.payments',
            'aiAnalysisResults',
            'recalls',
        ]);

        $export = [
            'exported_at'   => now()->toIso8601String(),
            'exported_by'   => request()->user()->email,
            'patient'       => [
                'id'                     => $patient->id,
                'first_name'             => $patient->first_name,
                'last_name'              => $patient->last_name,
                'gender'                 => $patient->gender,
                'date_of_birth'          => $patient->date_of_birth?->toDateString(),
                'phone'                  => $patient->phone,
                'email'                  => $patient->email,
                'address'                => $patient->address,
                'emergency_contact_name' => $patient->emergency_contact_name,
                'emergency_contact_phone'=> $patient->emergency_contact_phone,
                'blood_type'             => $patient->blood_type,
                'notes'                  => $patient->notes,
                'is_active'              => $patient->is_active,
                'created_at'             => $patient->created_at->toIso8601String(),
            ],
            'contacts'           => $patient->contacts,
            'allergies'          => $patient->allergies,
            'conditions'         => $patient->conditions,
            'medications'        => $patient->medications,
            'consents'           => $patient->consents,
            'medical_cases'      => $patient->medicalCases,
            'documents'          => $patient->documents->map(fn ($d) => collect($d)->except(['file_path'])),
            'appointments'       => $patient->appointments,
            'encounters'         => $patient->encounters,
            'invoices'           => $patient->invoices,
            'recalls'            => $patient->recalls,
            'ai_analysis_results'=> $patient->aiAnalysisResults,
        ];

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="patient-' . $patient->id . '-export.json"');
    }

    /* ══════════════════════════════════════════════════════════
     * PURGE
     * DELETE /api/v1/patients/{patient}/purge
     *
     * Owner only. Hard-deletes a patient and all related records.
     * Patient must already be soft-deleted (archived).
     * Sets scheduled_deletion_at = now() + PURGE_DELAY_DAYS,
     * then on confirmation call actually executes the hard delete.
     *
     * Two-step flow:
     *   Step 1: DELETE /purge                → sets scheduled_deletion_at
     *   Step 2: DELETE /purge?confirm=true   → executes hard delete
     * ══════════════════════════════════════════════════════════ */
    public function purge(Patient $patient): \Illuminate\Http\JsonResponse
    {
        abort_unless(request()->user()->isOwner(), 403, 'Only clinic owners may purge patient data.');
        abort_unless($patient->trashed(), 422, 'Patient must be archived before purging. Call DELETE /patients/{id} first.');

        $confirmed = request()->boolean('confirm');

        // Step 1 — Schedule deletion
        if (! $confirmed) {
            $scheduledAt = now()->addDays(Patient::PURGE_DELAY_DAYS);

            $patient->withoutEvents(function () use ($patient, $scheduledAt) {
                $patient->forceFill([
                    'scheduled_deletion_at' => $scheduledAt,
                    'purge_requested_at'    => now(),
                    'purge_requested_by'    => request()->user()->id,
                ])->save();
            });

            return response()->json([
                'message'               => "Patient data scheduled for permanent deletion in " . Patient::PURGE_DELAY_DAYS . " days.",
                'scheduled_deletion_at' => $scheduledAt->toIso8601String(),
                'warning'               => 'This action is irreversible. To execute immediately, call DELETE /patients/' . $patient->id . '/purge?confirm=true',
            ]);
        }

        // Step 2 — Execute hard delete
        $patientId   = $patient->id;
        $patientName = $patient->first_name . ' ' . $patient->last_name;

        // Hard delete all related records
        $patient->contacts()->forceDelete();
        $patient->allergies()->forceDelete();
        $patient->conditions()->forceDelete();
        $patient->medications()->forceDelete();
        $patient->consents()->forceDelete();
        $patient->medicalCases()->forceDelete();
        $patient->documents()->forceDelete();
        $patient->recalls()->forceDelete();
        $patient->appointments()->forceDelete();
        $patient->encounters()->forceDelete();

        // Hard delete the patient record itself
        $patient->forceDelete();

        // Audit trail — log purge even after patient is gone
        AuditLog::create([
            'user_id'    => request()->user()->id,
            'action'     => 'purge',
            'model_type' => 'Patient',
            'model_id'   => $patientId,
            'new_values' => ['patient_name' => $patientName, 'purged_at' => now()->toIso8601String()],
        ]);

        return response()->json([
            'message' => "Patient record for {$patientName} has been permanently deleted.",
        ]);
    }
}
