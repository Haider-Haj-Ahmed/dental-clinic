<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PatientMedicationResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'patient_id'    => $this->patient_id,
            'drug_name'     => $this->drug_name,
            'dose'          => $this->dose,
            'frequency'     => $this->frequency,
            'start_date'    => $this->start_date?->toDateString(),
            'end_date'      => $this->end_date?->toDateString(),
            'prescribed_by' => $this->prescribed_by,
            'notes'         => $this->notes,
            'provider'      => ProviderResource::make($this->whenLoaded('prescribedBy')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
