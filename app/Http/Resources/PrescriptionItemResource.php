<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
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
