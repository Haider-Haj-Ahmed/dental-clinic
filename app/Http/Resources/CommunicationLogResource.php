<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'provider_id'  => $this->provider_id,
            'recall_id'    => $this->recall_id,
            'channel'      => $this->channel,
            'direction'    => $this->direction,
            'subject'      => $this->subject,
            'body_preview' => $this->body_preview,
            'status'       => $this->status,
            'sent_at'      => $this->sent_at?->toIso8601String(),
            'patient'      => PatientResource::make($this->whenLoaded('patient')),
            'provider'     => ProviderResource::make($this->whenLoaded('provider')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
