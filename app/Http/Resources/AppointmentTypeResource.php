<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'name'                     => $this->name,
            'default_duration_minutes' => $this->default_duration_minutes,
            'color'                    => $this->color,
            'is_active'                => $this->is_active,
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
