<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RecallResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'patient_id'            => $this->patient_id,
            'due_date'              => $this->due_date?->toDateString(),
            'status'                => $this->status,
            'last_reminder_sent_at' => $this->last_reminder_sent_at?->toIso8601String(),
            'notes'                 => $this->notes,
            'patient'               => PatientResource::make($this->whenLoaded('patient')),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
