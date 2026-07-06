<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return $this->successResponse(message: 'Patient archived.');
    }

    /** POST /patients/{patient}/restore */
    public function restore(Patient $patient): PatientResource
    {
        $this->authorize('restore', $patient);

        $patient->restore();

        return PatientResource::make($patient->refresh());
    }
}
