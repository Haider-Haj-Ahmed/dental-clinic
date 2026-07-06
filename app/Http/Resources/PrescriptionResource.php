<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PrescriptionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'provider_id'  => $this->provider_id,
            'encounter_id' => $this->encounter_id,
            'issued_at'    => $this->issued_at?->toIso8601String(),
            'notes'        => $this->notes,
            'is_printed'   => $this->is_printed,
            'printed_at'   => $this->printed_at?->toIso8601String(),
            'patient'      => PatientResource::make($this->whenLoaded('patient')),
            'provider'     => ProviderResource::make($this->whenLoaded('provider')),
            'items'        => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
