<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientConditionRequest;
use App\Http\Requests\UpdatePatientConditionRequest;
use App\Http\Resources\PatientConditionResource;
use App\Models\Patient;
use App\Models\PatientCondition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientConditionController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientCondition::class);

        return PatientConditionResource::collection(
            $patient->conditions()
                ->with('notedBy')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByRaw("FIELD(status, 'active', 'resolved')")
                ->orderBy('onset_date', 'desc')
                ->get()
        );
    }

    public function store(StorePatientConditionRequest $request, Patient $patient): PatientConditionResource
    {
        $this->authorize('create', PatientCondition::class);

        $condition = $patient->conditions()->create($request->validated());

        return PatientConditionResource::make($condition->load('notedBy'));
    }

    public function show(Patient $patient, PatientCondition $condition): PatientConditionResource
    {
        $this->authorize('view', $condition);
        abort_if($condition->patient_id !== $patient->id, 404);

        return PatientConditionResource::make($condition->load('notedBy'));
    }

    public function update(UpdatePatientConditionRequest $request, Patient $patient, PatientCondition $condition): PatientConditionResource
    {
        $this->authorize('update', $condition);
        abort_if($condition->patient_id !== $patient->id, 404);

        $condition->update($request->validated());

        return PatientConditionResource::make($condition->refresh()->load('notedBy'));
    }

    public function destroy(Patient $patient, PatientCondition $condition): Response
    {
        $this->authorize('delete', $condition);
        abort_if($condition->patient_id !== $patient->id, 404);

        $condition->delete();

        return response()->noContent();
    }
}
