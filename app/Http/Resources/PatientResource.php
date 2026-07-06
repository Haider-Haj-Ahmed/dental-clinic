<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PatientResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'first_name'              => $this->first_name,
            'last_name'               => $this->last_name,
            'full_name'               => trim($this->first_name.' '.$this->last_name),
            'gender'                  => $this->gender,
            'date_of_birth'           => $this->date_of_birth?->toDateString(),
            'phone'                   => $this->phone,
            'email'                   => $this->email,
            'address'                 => $this->address,
            'emergency_contact_name'  => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'notes'                   => $this->notes,
            'is_active'               => $this->is_active,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
            'deleted_at'              => $this->deleted_at,
        ];
    }
}
