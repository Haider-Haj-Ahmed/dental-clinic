<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'invoice_id'        => $this->invoice_id,
            'patient_id'        => $this->patient_id,
            'payment_method_id' => $this->payment_method_id,
            'recorded_by'       => $this->recorded_by,
            'amount'            => $this->amount,
            'paid_at'           => $this->paid_at?->toDateString(),
            'reference'         => $this->reference,
            'notes'             => $this->notes,
            'payment_method'    => PaymentMethodResource::make($this->whenLoaded('paymentMethod')),
            'recorder'          => UserResource::make($this->whenLoaded('recordedBy')),
            'created_at'        => $this->created_at,
        ];
    }
}
