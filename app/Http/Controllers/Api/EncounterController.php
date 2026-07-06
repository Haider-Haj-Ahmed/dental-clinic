<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEncounterRequest;
use App\Http\Requests\UpdateEncounterRequest;
use App\Http\Resources\EncounterResource;
use App\Models\Encounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EncounterController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Encounter::class, 'encounter');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $encounters = Encounter::query()
            ->with(['patient', 'provider'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('encounter_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('encounter_date', '<=', $request->date('date_to')))
            ->when($request->filled('is_locked'), fn ($q) => $q->where('is_locked', $request->boolean('is_locked')))
            ->orderBy('encounter_date', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return EncounterResource::collection($encounters);
    }

    public function store(StoreEncounterRequest $request): EncounterResource
    {
        $encounter = Encounter::query()->create($request->validated());

        return EncounterResource::make($encounter->load(['patient', 'provider']));
    }

    public function show(Encounter $encounter): EncounterResource
    {
        return EncounterResource::make($encounter->load(['patient', 'provider', 'lockedBy']));
    }

    public function update(UpdateEncounterRequest $request, Encounter $encounter): EncounterResource
    {
        abort_if($encounter->is_locked, 422, 'This encounter is locked and cannot be edited.');

        $encounter->update($request->validated());

        return EncounterResource::make($encounter->refresh()->load(['patient', 'provider']));
    }

    public function destroy(Encounter $encounter): Response
    {
        abort_if($encounter->is_locked, 422, 'Locked encounters cannot be deleted.');

        $encounter->delete();

        return $this->noContentResponse();
    }

    /** POST /encounters/{encounter}/lock */
    public function lock(Request $request, Encounter $encounter): EncounterResource
    {
        $this->authorize('lock', $encounter);

        abort_if($encounter->is_locked, 422, 'Encounter is already locked.');

        $encounter->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $request->user()->id,
        ]);

        return EncounterResource::make($encounter->refresh()->load(['patient', 'provider', 'lockedBy']));
    }

    /** POST /encounters/{encounter}/unlock */
    public function unlock(Request $request, Encounter $encounter): EncounterResource
    {
        $this->authorize('lock', $encounter);

        abort_if(! $encounter->is_locked, 422, 'Encounter is not locked.');

        $encounter->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return EncounterResource::make($encounter->refresh()->load(['patient', 'provider']));
    }
}
