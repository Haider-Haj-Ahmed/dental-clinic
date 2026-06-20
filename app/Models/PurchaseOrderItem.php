<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'inventory_item_id',
        'quantity_ordered', 'quantity_received', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered'  => 'integer',
            'quantity_received' => 'integer',
            'unit_cost'         => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo  { return $this->belongsTo(PurchaseOrder::class); }
    public function inventoryItem(): BelongsTo  { return $this->belongsTo(InventoryItem::class); }
}
