<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'provider_id'  => $this->provider_id,
            'title'        => $this->title,
            'status'       => $this->status,
            'total_fee'    => $this->total_fee,
            'notes'        => $this->notes,
            'presented_at' => $this->presented_at?->toIso8601String(),
            'accepted_at'  => $this->accepted_at?->toIso8601String(),
            'rejected_at'  => $this->rejected_at?->toIso8601String(),
            'created_by'   => $this->created_by,
            'patient'      => PatientResource::make($this->whenLoaded('patient')),
            'provider'     => ProviderResource::make($this->whenLoaded('provider')),
            'creator'      => UserResource::make($this->whenLoaded('creator')),
            'items'        => TreatmentPlanItemResource::collection($this->whenLoaded('items')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
