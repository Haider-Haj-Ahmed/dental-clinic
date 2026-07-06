<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentMethodResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
