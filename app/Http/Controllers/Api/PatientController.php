<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
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
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate((int) $request->integer('per_page', 20))
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

    public function destroy(Patient $patient): Response
    {
        $patient->delete();

        return response()->noContent();
    }
}
