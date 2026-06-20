<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientMedicationRequest;
use App\Http\Requests\UpdatePatientMedicationRequest;
use App\Http\Resources\PatientMedicationResource;
use App\Models\Patient;
use App\Models\PatientMedication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientMedicationController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientMedication::class);

        return PatientMedicationResource::collection(
            $patient->medications()
                ->with('prescribedBy')
                ->when($request->boolean('active_only'), fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()))
                ->orderBy('drug_name')
                ->get()
        );
    }

    public function store(StorePatientMedicationRequest $request, Patient $patient): PatientMedicationResource
    {
        $this->authorize('create', PatientMedication::class);

        $medication = $patient->medications()->create($request->validated());

        return PatientMedicationResource::make($medication->load('prescribedBy'));
    }

    public function show(Patient $patient, PatientMedication $medication): PatientMedicationResource
    {
        $this->authorize('view', $medication);
        abort_if($medication->patient_id !== $patient->id, 404);

        return PatientMedicationResource::make($medication->load('prescribedBy'));
    }

    public function update(UpdatePatientMedicationRequest $request, Patient $patient, PatientMedication $medication): PatientMedicationResource
    {
        $this->authorize('update', $medication);
        abort_if($medication->patient_id !== $patient->id, 404);

        $medication->update($request->validated());

        return PatientMedicationResource::make($medication->refresh()->load('prescribedBy'));
    }

    public function destroy(Patient $patient, PatientMedication $medication): Response
    {
        $this->authorize('delete', $medication);
        abort_if($medication->patient_id !== $patient->id, 404);

        $medication->delete();

        return response()->noContent();
    }
}
