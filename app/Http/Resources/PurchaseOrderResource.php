<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'supplier_id' => $this->supplier_id,
            'status'      => $this->status,
            'ordered_at'  => $this->ordered_at?->toDateString(),
            'expected_at' => $this->expected_at?->toDateString(),
            'received_at' => $this->received_at?->toDateString(),
            'notes'       => $this->notes,
            'created_by'  => $this->created_by,
            'supplier'    => SupplierResource::make($this->whenLoaded('supplier')),
            'creator'     => UserResource::make($this->whenLoaded('creator')),
            'items'       => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
