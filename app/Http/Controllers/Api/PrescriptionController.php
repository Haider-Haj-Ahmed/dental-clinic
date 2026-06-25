<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Prescription::class, 'prescription');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $prescriptions = Prescription::query()
            ->with(['patient', 'provider', 'items'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('encounter_id'), fn ($q) => $q->where('encounter_id', $request->integer('encounter_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issued_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issued_at', '<=', $request->date('date_to')))
            ->orderBy('issued_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PrescriptionResource::collection($prescriptions);
    }

    public function store(StorePrescriptionRequest $request): PrescriptionResource
    {
        $provider = $request->user()->providerProfile;
        abort_if(! $provider && ! $request->user()->isOwner(), 422, 'A provider profile is required to issue a prescription.');

        $prescription = DB::transaction(function () use ($request, $provider) {
            $prescription = Prescription::query()->create([
                'patient_id'   => $request->integer('patient_id'),
                'provider_id'  => $provider?->id ?? $request->integer('provider_id'),
                'encounter_id' => $request->integer('encounter_id') ?: null,
                'issued_at'    => $request->input('issued_at', now()),
                'notes'        => $request->input('notes'),
            ]);

            foreach ($request->input('items') as $item) {
                $prescription->items()->create($item);
            }

            return $prescription;
        });

        return PrescriptionResource::make($prescription->load(['patient', 'provider', 'items']));
    }

    public function show(Prescription $prescription): PrescriptionResource
    {
        return PrescriptionResource::make($prescription->load(['patient', 'provider', 'items']));
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): PrescriptionResource
    {
        $data = $request->validated();

        if ($request->boolean('is_printed') && ! $prescription->is_printed) {
            $data['printed_at'] = now();
        }

        $prescription->update($data);

        return PrescriptionResource::make($prescription->refresh()->load(['patient', 'provider', 'items']));
    }

    public function destroy(Prescription $prescription): Response
    {
        $prescription->delete();

        return response()->noContent();
    }
}
