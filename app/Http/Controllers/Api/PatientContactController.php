<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientContactRequest;
use App\Http\Requests\UpdatePatientContactRequest;
use App\Http\Resources\PatientContactResource;
use App\Models\Patient;
use App\Models\PatientContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientContactController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientContact::class);

        return PatientContactResource::collection(
            $patient->contacts()->orderBy('is_emergency', 'desc')->orderBy('id')->get()
        );
    }

    public function store(StorePatientContactRequest $request, Patient $patient): PatientContactResource
    {
        $this->authorize('create', PatientContact::class);

        $contact = $patient->contacts()->create($request->validated());

        return PatientContactResource::make($contact);
    }

    public function show(Patient $patient, PatientContact $contact): PatientContactResource
    {
        $this->authorize('view', $contact);
        $this->assertBelongsToPatient($contact->patient_id, $patient->id);

        return PatientContactResource::make($contact);
    }

    public function update(UpdatePatientContactRequest $request, Patient $patient, PatientContact $contact): PatientContactResource
    {
        $this->authorize('update', $contact);
        $this->assertBelongsToPatient($contact->patient_id, $patient->id);

        $contact->update($request->validated());

        return PatientContactResource::make($contact->refresh());
    }

    public function destroy(Patient $patient, PatientContact $contact): Response
    {
        $this->authorize('delete', $contact);
        $this->assertBelongsToPatient($contact->patient_id, $patient->id);

        $contact->delete();

        return $this->noContentResponse();
    }

    private function assertBelongsToPatient(int $recordPatientId, int $routePatientId): void
    {
        abort_if($recordPatientId !== $routePatientId, 404);
    }
}
