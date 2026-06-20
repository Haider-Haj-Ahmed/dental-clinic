<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_plan_id', 'payment_id',
        'due_date', 'amount', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'immutable_date',
            'paid_at'  => 'immutable_date',
            'amount'   => 'decimal:2',
        ];
    }

    public function plan(): BelongsTo    { return $this->belongsTo(PaymentPlan::class, 'payment_plan_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
