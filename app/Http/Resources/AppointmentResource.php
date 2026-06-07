<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'provider_id'         => $this->provider_id,
            'appointment_type_id' => $this->appointment_type_id,
            'operatory_id'        => $this->operatory_id,
            'start_at'            => $this->start_at?->toIso8601String(),
            'end_at'              => $this->end_at?->toIso8601String(),
            'status'              => $this->status,
            'chief_complaint'     => $this->chief_complaint,
            'notes'               => $this->notes,
            'color'               => $this->color,
            'cancelled_at'        => $this->cancelled_at?->toIso8601String(),
            'reminder_sent_at'    => $this->reminder_sent_at?->toIso8601String(),
            'created_by'          => $this->created_by,
            'patient'             => PatientResource::make($this->whenLoaded('patient')),
            'provider'            => ProviderResource::make($this->whenLoaded('provider')),
            'appointment_type'    => AppointmentTypeResource::make($this->whenLoaded('appointmentType')),
            'operatory'           => OperatoryResource::make($this->whenLoaded('operatory')),
            'creator'             => UserResource::make($this->whenLoaded('creator')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
