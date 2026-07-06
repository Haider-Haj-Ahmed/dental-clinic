<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PurchaseOrderItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'purchase_order_id'   => $this->purchase_order_id,
            'inventory_item_id'   => $this->inventory_item_id,
            'quantity_ordered'    => $this->quantity_ordered,
            'quantity_received'   => $this->quantity_received,
            'unit_cost'           => $this->unit_cost,
            'inventory_item'      => InventoryItemResource::make($this->whenLoaded('inventoryItem')),
        ];
    }
}
