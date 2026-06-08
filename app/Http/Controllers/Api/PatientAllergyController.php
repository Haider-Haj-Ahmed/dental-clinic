<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientAllergyRequest;
use App\Http\Requests\UpdatePatientAllergyRequest;
use App\Http\Resources\PatientAllergyResource;
use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientAllergyController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientAllergy::class);

        return PatientAllergyResource::collection(
            $patient->allergies()->with('notedBy')->orderBy('allergen')->get()
        );
    }

    public function store(StorePatientAllergyRequest $request, Patient $patient): PatientAllergyResource
    {
        $this->authorize('create', PatientAllergy::class);

        $allergy = $patient->allergies()->create($request->validated());

        return PatientAllergyResource::make($allergy->load('notedBy'));
    }

    public function show(Patient $patient, PatientAllergy $allergy): PatientAllergyResource
    {
        $this->authorize('view', $allergy);
        abort_if($allergy->patient_id !== $patient->id, 404);

        return PatientAllergyResource::make($allergy->load('notedBy'));
    }

    public function update(UpdatePatientAllergyRequest $request, Patient $patient, PatientAllergy $allergy): PatientAllergyResource
    {
        $this->authorize('update', $allergy);
        abort_if($allergy->patient_id !== $patient->id, 404);

        $allergy->update($request->validated());

        return PatientAllergyResource::make($allergy->refresh()->load('notedBy'));
    }

    public function destroy(Patient $patient, PatientAllergy $allergy): Response
    {
        $this->authorize('delete', $allergy);
        abort_if($allergy->patient_id !== $patient->id, 404);

        $allergy->delete();

        return response()->noContent();
    }
}
