<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCondition extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'patient_id', 'condition', 'icd_code', 'status',
        'onset_date', 'noted_by', 'noted_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'onset_date' => 'date',
            'noted_at'   => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function notedBy(): BelongsTo { return $this->belongsTo(Provider::class, 'noted_by'); }
}
