<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentPlanItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'payment_plan_id' => $this->payment_plan_id,
            'payment_id'      => $this->payment_id,
            'due_date'        => $this->due_date?->toDateString(),
            'amount'          => $this->amount,
            'paid_at'         => $this->paid_at?->toDateString(),
            'is_paid'         => $this->paid_at !== null,
        ];
    }
}
