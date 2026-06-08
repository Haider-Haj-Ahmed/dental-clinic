<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    protected $fillable = [
        'patient_id',
        'consent_type',
        'signed_at',
        'signed_by_patient',
        'witness_provider_id',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'signed_at'         => 'datetime',
            'signed_by_patient' => 'boolean',
        ];
    }

    public function patient(): BelongsTo         { return $this->belongsTo(Patient::class); }
    public function witnessProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'witness_provider_id'); }
}
