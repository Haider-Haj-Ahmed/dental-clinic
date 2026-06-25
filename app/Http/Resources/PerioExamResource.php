<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerioExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'patient_id'  => $this->patient_id,
            'provider_id' => $this->provider_id,
            'exam_date'   => $this->exam_date?->toDateString(),
            'notes'       => $this->notes,
            'measures_count' => $this->whenCounted('measures'),
            'patient'     => PatientResource::make($this->whenLoaded('patient')),
            'provider'    => ProviderResource::make($this->whenLoaded('provider')),
            'measures'    => PerioMeasureResource::collection($this->whenLoaded('measures')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
