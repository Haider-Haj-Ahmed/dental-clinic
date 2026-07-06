<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SupplierResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'contact_name' => $this->contact_name,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'address'      => $this->address,
            'notes'        => $this->notes,
            'is_active'    => $this->is_active,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
