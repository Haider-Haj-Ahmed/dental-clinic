<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlan extends Model
{
    public const STATUS_DRAFT      = 'draft';
    public const STATUS_PRESENTED  = 'presented';
    public const STATUS_ACCEPTED   = 'accepted';
    public const STATUS_REJECTED   = 'rejected';

    protected $fillable = [
        'patient_id', 'provider_id', 'title', 'status',
        'total_fee', 'notes', 'presented_at', 'accepted_at', 'rejected_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_fee'    => 'decimal:2',
            'presented_at' => 'datetime',
            'accepted_at'  => 'datetime',
            'rejected_at'  => 'datetime',
        ];
    }

    public function patient(): BelongsTo  { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany      { return $this->hasMany(TreatmentPlanItem::class); }
}
