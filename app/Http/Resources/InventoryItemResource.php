<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class InventoryItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'supplier_id'   => $this->supplier_id,
            'name'          => $this->name,
            'sku'           => $this->sku,
            'category'      => $this->category,
            'unit'          => $this->unit,
            'current_stock' => $this->current_stock,
            'reorder_level' => $this->reorder_level,
            'is_low_stock'  => $this->isLowStock(),
            'unit_cost'     => $this->unit_cost,
            'is_active'     => $this->is_active,
            'supplier'      => SupplierResource::make($this->whenLoaded('supplier')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
