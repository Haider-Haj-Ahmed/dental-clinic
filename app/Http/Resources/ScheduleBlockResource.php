<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ScheduleBlockResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'provider_id'  => $this->provider_id,
            'operatory_id' => $this->operatory_id,
            'start_at'     => $this->start_at?->toIso8601String(),
            'end_at'       => $this->end_at?->toIso8601String(),
            'reason'       => $this->reason,
            'created_by'   => $this->created_by,
            'provider'     => ProviderResource::make($this->whenLoaded('provider')),
            'operatory'    => OperatoryResource::make($this->whenLoaded('operatory')),
            'creator'      => UserResource::make($this->whenLoaded('creator')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
