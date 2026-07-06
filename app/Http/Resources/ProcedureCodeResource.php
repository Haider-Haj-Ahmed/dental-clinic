<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProcedureCodeResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'description' => $this->description,
            'fee_default' => $this->fee_default,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
