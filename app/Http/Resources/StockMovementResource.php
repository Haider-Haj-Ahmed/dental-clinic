<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'movement_type'     => $this->movement_type,
            'quantity'          => $this->quantity,
            'stock_after'       => $this->stock_after,
            'reason'            => $this->reason,
            'reference'         => $this->reference,
            'performed_at'      => $this->performed_at?->toIso8601String(),
            'performed_by'      => UserResource::make($this->whenLoaded('performedBy')),
            'created_at'        => $this->created_at,
        ];
    }
}
