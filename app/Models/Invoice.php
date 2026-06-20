<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_PAID      = 'paid';
    public const STATUS_PARTIAL   = 'partial';
    public const STATUS_VOID      = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_FINALIZED,
        self::STATUS_PAID,
        self::STATUS_PARTIAL,
        self::STATUS_VOID,
    ];

    protected $fillable = [
        'patient_id', 'appointment_id', 'provider_id',
        'status', 'issued_at', 'due_at',
        'subtotal', 'discount', 'tax', 'total',
        'notes', 'finalized_by', 'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_date',
            'due_at'    => 'immutable_date',
            'subtotal'  => 'decimal:2',
            'discount'  => 'decimal:2',
            'tax'       => 'decimal:2',
            'total'     => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function provider(): BelongsTo    { return $this->belongsTo(Provider::class); }
    public function finalizedBy(): BelongsTo { return $this->belongsTo(User::class, 'finalized_by'); }
    public function voidedBy(): BelongsTo    { return $this->belongsTo(User::class, 'voided_by'); }
    public function items(): HasMany         { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany      { return $this->hasMany(Payment::class); }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $this->update([
            'subtotal' => $subtotal,
            'total'    => max(0, $subtotal - $this->discount + $this->tax),
        ]);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }
}
