<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'operatory_id'   => $this->operatory_id,
            'name'           => $this->name,
            'specialty'      => $this->specialty,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'license_number' => $this->license_number,
            'bio'            => $this->bio,
            'is_active'      => $this->is_active,
            'user'           => UserResource::make($this->whenLoaded('user')),
            'operatory'      => OperatoryResource::make($this->whenLoaded('operatory')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
