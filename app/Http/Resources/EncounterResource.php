<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncounterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'appointment_id' => $this->appointment_id,
            'patient_id'     => $this->patient_id,
            'provider_id'    => $this->provider_id,
            'encounter_date' => $this->encounter_date?->toDateString(),
            'subjective'     => $this->subjective,
            'objective'      => $this->objective,
            'assessment'     => $this->assessment,
            'plan'           => $this->plan,
            'is_locked'      => $this->is_locked,
            'locked_at'      => $this->locked_at?->toIso8601String(),
            'locked_by'      => $this->locked_by,
            'patient'        => PatientResource::make($this->whenLoaded('patient')),
            'provider'       => ProviderResource::make($this->whenLoaded('provider')),
            'locked_by_user' => UserResource::make($this->whenLoaded('lockedBy')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
