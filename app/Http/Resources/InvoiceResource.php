<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'patient_id'     => $this->patient_id,
            'appointment_id' => $this->appointment_id,
            'provider_id'    => $this->provider_id,
            'status'         => $this->status,
            'issued_at'      => $this->issued_at?->toDateString(),
            'due_at'         => $this->due_at?->toDateString(),
            'subtotal'       => $this->subtotal,
            'discount'       => $this->discount,
            'tax'            => $this->tax,
            'total'          => $this->total,
            'amount_paid'    => $this->whenCounted('payments', fn () => $this->amountPaid()),
            'notes'          => $this->notes,
            'finalized_by'   => $this->finalized_by,
            'voided_by'      => $this->voided_by,
            'patient'        => PatientResource::make($this->whenLoaded('patient')),
            'provider'       => ProviderResource::make($this->whenLoaded('provider')),
            'items'          => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments'       => PaymentResource::make($this->whenLoaded('payments')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
