<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOdontogramEntryRequest;
use App\Http\Requests\UpdateOdontogramEntryRequest;
use App\Http\Resources\OdontogramEntryResource;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OdontogramController extends Controller
{
    /** GET /patients/{patient}/odontogram — full mouth view */
    public function patientOdontogram(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OdontogramEntry::class);

        $entries = OdontogramEntry::query()
            ->where('patient_id', $patient->id)
            ->with('provider')
            ->when($request->filled('tooth_number'), fn ($q) => $q->where('tooth_number', $request->integer('tooth_number')))
            ->when($request->filled('entry_type'), fn ($q) => $q->where('entry_type', $request->string('entry_type')))
            ->orderBy('tooth_number')
            ->orderBy('recorded_at', 'desc')
            ->get();

        return OdontogramEntryResource::collection($entries);
    }

    /** GET /encounters/{encounter}/odontogram-entries */
    public function encounterEntries(Request $request, \App\Models\Encounter $encounter): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OdontogramEntry::class);

        return OdontogramEntryResource::collection(
            OdontogramEntry::query()
                ->where('encounter_id', $encounter->id)
                ->with('provider')
                ->orderBy('tooth_number')
                ->get()
        );
    }

    /** POST /encounters/{encounter}/odontogram-entries */
    public function store(StoreOdontogramEntryRequest $request, \App\Models\Encounter $encounter): OdontogramEntryResource
    {
        $this->authorize('create', OdontogramEntry::class);

        abort_if($encounter->is_locked, 422, 'Cannot add entries to a locked encounter.');

        $payload = $request->validated();
        $payload['encounter_id'] = $encounter->id;
        $payload['patient_id']   = $encounter->patient_id;
        $payload['provider_id']  = $encounter->provider_id;
        $payload['recorded_at']  = $payload['recorded_at'] ?? now();

        $entry = OdontogramEntry::query()->create($payload);

        return OdontogramEntryResource::make($entry->load('provider'));
    }

    /** PUT /odontogram-entries/{entry} */
    public function update(UpdateOdontogramEntryRequest $request, OdontogramEntry $odontogramEntry): OdontogramEntryResource
    {
        $this->authorize('update', $odontogramEntry);

        $odontogramEntry->update($request->validated());

        return OdontogramEntryResource::make($odontogramEntry->refresh()->load('provider'));
    }

    /** DELETE /odontogram-entries/{entry} */
    public function destroy(OdontogramEntry $odontogramEntry): Response
    {
        $this->authorize('delete', $odontogramEntry);

        $odontogramEntry->delete();

        return response()->noContent();
    }
}
