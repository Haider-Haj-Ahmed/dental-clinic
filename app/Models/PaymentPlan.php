<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPlan extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'patient_id', 'invoice_id',
        'total_amount', 'installments', 'start_date',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'installments' => 'integer',
            'start_date'   => 'immutable_date',
        ];
    }

    public function patient(): BelongsTo  { return $this->belongsTo(Patient::class); }
    public function invoice(): BelongsTo  { return $this->belongsTo(Invoice::class); }
    public function items(): HasMany      { return $this->hasMany(PaymentPlanItem::class); }
}
