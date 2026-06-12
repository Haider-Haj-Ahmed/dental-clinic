<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_IN         = 'in';
    public const TYPE_OUT        = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_EXPIRED    = 'expired';

    public const TYPES = [
        self::TYPE_IN,
        self::TYPE_OUT,
        self::TYPE_ADJUSTMENT,
        self::TYPE_EXPIRED,
    ];

    protected $fillable = [
        'inventory_item_id', 'performed_by',
        'movement_type', 'quantity', 'stock_after',
        'reason', 'reference', 'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'     => 'integer',
            'stock_after'  => 'integer',
            'performed_at' => 'immutable_datetime',
        ];
    }

    public function inventoryItem(): BelongsTo { return $this->belongsTo(InventoryItem::class); }
    public function performedBy(): BelongsTo   { return $this->belongsTo(User::class, 'performed_by'); }
}
