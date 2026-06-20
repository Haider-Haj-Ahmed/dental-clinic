<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientMedicalCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'provider_id'         => $this->provider_id,
            'case_date'           => $this->case_date?->toDateString(),
            'case_type'           => $this->case_type,
            'chief_complaint'     => $this->chief_complaint,
            'diagnosis'           => $this->diagnosis,
            'treatment_performed' => $this->treatment_performed,
            'outcome'             => $this->outcome,
            'is_external'         => $this->is_external,
            'previous_clinic'     => $this->previous_clinic,
            'previous_dentist'    => $this->previous_dentist,
            'notes'               => $this->notes,
            'created_by'          => $this->created_by,
            'provider'            => ProviderResource::make($this->whenLoaded('provider')),
            'creator'             => UserResource::make($this->whenLoaded('creator')),
            'documents_count'     => $this->whenCounted('documents'),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
