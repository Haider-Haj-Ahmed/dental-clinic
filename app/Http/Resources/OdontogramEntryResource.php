<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OdontogramEntryResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'encounter_id' => $this->encounter_id,
            'patient_id'   => $this->patient_id,
            'provider_id'  => $this->provider_id,
            'tooth_number' => $this->tooth_number,
            'surface'      => $this->surface,
            'entry_type'   => $this->entry_type,
            'code'         => $this->code,
            'color_hex'    => $this->color_hex,
            'notes'        => $this->notes,
            'recorded_at'  => $this->recorded_at?->toIso8601String(),
            'provider'     => ProviderResource::make($this->whenLoaded('provider')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
