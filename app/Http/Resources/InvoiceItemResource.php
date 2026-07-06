<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class InvoiceItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'invoice_id'        => $this->invoice_id,
            'procedure_code_id' => $this->procedure_code_id,
            'description'       => $this->description,
            'tooth_number'      => $this->tooth_number,
            'qty'               => $this->qty,
            'unit_price'        => $this->unit_price,
            'total'             => $this->total,
            'procedure_code'    => ProcedureCodeResource::make($this->whenLoaded('procedureCode')),
            'created_at'        => $this->created_at,
        ];
    }
}
