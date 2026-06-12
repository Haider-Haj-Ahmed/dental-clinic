<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SENT      = 'sent';
    public const STATUS_RECEIVED  = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'supplier_id', 'created_by', 'status',
        'ordered_at', 'expected_at', 'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at'  => 'immutable_date',
            'expected_at' => 'immutable_date',
            'received_at' => 'immutable_date',
        ];
    }

    public function supplier(): BelongsTo  { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany       { return $this->hasMany(PurchaseOrderItem::class); }
}
