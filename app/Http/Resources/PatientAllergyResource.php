<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PatientAllergyResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'patient_id' => $this->patient_id,
            'allergen'   => $this->allergen,
            'reaction'   => $this->reaction,
            'severity'   => $this->severity,
            'noted_by'   => $this->noted_by,
            'noted_at'   => $this->noted_at,
            'notes'      => $this->notes,
            'provider'   => ProviderResource::make($this->whenLoaded('notedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
