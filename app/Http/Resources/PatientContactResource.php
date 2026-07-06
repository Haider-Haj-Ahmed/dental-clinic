<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PatientContactResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'label'        => $this->label,
            'name'         => $this->name,
            'phone'        => $this->phone,
            'relationship' => $this->relationship,
            'is_emergency' => $this->is_emergency,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
