<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientMedicalCaseRequest;
use App\Http\Requests\UpdatePatientMedicalCaseRequest;
use App\Http\Resources\PatientMedicalCaseResource;
use App\Models\Patient;
use App\Models\PatientMedicalCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientMedicalCaseController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientMedicalCase::class);

        $cases = $patient->medicalCases()
            ->withCount('documents')
            ->with(['provider', 'creator'])
            ->when($request->filled('case_type'), fn ($q) => $q->where('case_type', $request->string('case_type')))
            ->when($request->filled('is_external'), fn ($q) => $q->where('is_external', $request->boolean('is_external')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('case_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('case_date', '<=', $request->date('date_to')))
            ->orderBy('case_date', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PatientMedicalCaseResource::collection($cases);
    }

    public function store(StorePatientMedicalCaseRequest $request, Patient $patient): PatientMedicalCaseResource
    {
        $this->authorize('create', PatientMedicalCase::class);

        $payload = $request->validated();
        $payload['created_by'] = $request->user()->id;

        // If the authenticated user is a provider and no provider_id was given,
        // default to their own provider profile
        if ($request->user()->isProvider() && empty($payload['provider_id'])) {
            $payload['provider_id'] = $request->user()->providerProfile?->id;
        }

        $case = $patient->medicalCases()->create($payload);

        return PatientMedicalCaseResource::make($case->load(['provider', 'creator']));
    }

    public function show(Patient $patient, PatientMedicalCase $medicalCase): PatientMedicalCaseResource
    {
        $this->authorize('view', $medicalCase);
        abort_if($medicalCase->patient_id !== $patient->id, 404);

        return PatientMedicalCaseResource::make(
            $medicalCase->loadCount('documents')->load(['provider', 'creator'])
        );
    }

    public function update(UpdatePatientMedicalCaseRequest $request, Patient $patient, PatientMedicalCase $medicalCase): PatientMedicalCaseResource
    {
        $this->authorize('update', $medicalCase);
        abort_if($medicalCase->patient_id !== $patient->id, 404);

        $medicalCase->update($request->validated());

        return PatientMedicalCaseResource::make(
            $medicalCase->refresh()->loadCount('documents')->load(['provider', 'creator'])
        );
    }

    public function destroy(Patient $patient, PatientMedicalCase $medicalCase): Response
    {
        $this->authorize('delete', $medicalCase);
        abort_if($medicalCase->patient_id !== $patient->id, 404);

        $medicalCase->delete();

        return response()->noContent();
    }
}
