<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientConsentRequest;
use App\Http\Requests\UpdatePatientConsentRequest;
use App\Http\Resources\PatientConsentResource;
use App\Models\Patient;
use App\Models\PatientConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientConsentController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientConsent::class);

        return PatientConsentResource::collection(
            $patient->consents()->with('witnessProvider')->orderBy('signed_at', 'desc')->get()
        );
    }

    public function store(StorePatientConsentRequest $request, Patient $patient): PatientConsentResource
    {
        $this->authorize('create', PatientConsent::class);

        $consent = $patient->consents()->create($request->validated());

        return PatientConsentResource::make($consent->load('witnessProvider'));
    }

    public function show(Patient $patient, PatientConsent $consent): PatientConsentResource
    {
        $this->authorize('view', $consent);
        abort_if($consent->patient_id !== $patient->id, 404);

        return PatientConsentResource::make($consent->load('witnessProvider'));
    }

    public function update(UpdatePatientConsentRequest $request, Patient $patient, PatientConsent $consent): PatientConsentResource
    {
        $this->authorize('update', $consent);
        abort_if($consent->patient_id !== $patient->id, 404);

        $consent->update($request->validated());

        return PatientConsentResource::make($consent->refresh()->load('witnessProvider'));
    }

    public function destroy(Patient $patient, PatientConsent $consent): Response
    {
        $this->authorize('delete', $consent);
        abort_if($consent->patient_id !== $patient->id, 404);

        $consent->delete();

        return $this->noContentResponse();
    }
}
