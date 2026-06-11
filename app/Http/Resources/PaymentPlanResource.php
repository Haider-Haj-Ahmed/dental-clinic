<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'invoice_id'   => $this->invoice_id,
            'total_amount' => $this->total_amount,
            'installments' => $this->installments,
            'start_date'   => $this->start_date?->toDateString(),
            'status'       => $this->status,
            'notes'        => $this->notes,
            'patient'      => PatientResource::make($this->whenLoaded('patient')),
            'invoice'      => InvoiceResource::make($this->whenLoaded('invoice')),
            'items'        => PaymentPlanItemResource::collection($this->whenLoaded('items')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
