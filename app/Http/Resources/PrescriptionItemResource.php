<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PrescriptionItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'prescription_id' => $this->prescription_id,
            'drug_name'       => $this->drug_name,
            'dose'            => $this->dose,
            'frequency'       => $this->frequency,
            'duration'        => $this->duration,
            'quantity'        => $this->quantity,
            'instructions'    => $this->instructions,
        ];
    }
}
