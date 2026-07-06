<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TreatmentPlanItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'treatment_plan_id' => $this->treatment_plan_id,
            'procedure_code_id' => $this->procedure_code_id,
            'tooth_number'      => $this->tooth_number,
            'surface'           => $this->surface,
            'description'       => $this->description,
            'fee'               => $this->fee,
            'sort_order'        => $this->sort_order,
            'status'            => $this->status,
            'procedure_code'    => ProcedureCodeResource::make($this->whenLoaded('procedureCode')),
        ];
    }
}
