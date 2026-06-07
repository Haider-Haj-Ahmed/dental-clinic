<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedication extends Model
{
    protected $fillable = [
        'patient_id', 'drug_name', 'dose', 'frequency',
        'start_date', 'end_date', 'prescribed_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function prescribedBy(): BelongsTo  { return $this->belongsTo(Provider::class, 'prescribed_by'); }
}
