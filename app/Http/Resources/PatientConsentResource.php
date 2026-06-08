<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientConsentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'consent_type'        => $this->consent_type,
            'signed_at'           => $this->signed_at,
            'signed_by_patient'   => $this->signed_by_patient,
            'witness_provider_id' => $this->witness_provider_id,
            'file_path'           => $this->file_path,
            'witness_provider'    => ProviderResource::make($this->whenLoaded('witnessProvider')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
