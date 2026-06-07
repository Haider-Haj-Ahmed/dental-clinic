<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encounter extends Model
{
    protected $fillable = [
        'appointment_id', 'patient_id', 'provider_id',
        'encounter_date', 'subjective', 'objective', 'assessment', 'plan',
        'is_locked', 'locked_at', 'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
            'is_locked'      => 'boolean',
            'locked_at'      => 'datetime',
        ];
    }

    public function patient(): BelongsTo      { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo     { return $this->belongsTo(Provider::class); }
    public function appointment(): BelongsTo  { return $this->belongsTo(Appointment::class); }
    public function lockedBy(): BelongsTo     { return $this->belongsTo(User::class, 'locked_by'); }
    public function odontogramEntries(): HasMany { return $this->hasMany(OdontogramEntry::class); }
    public function prescriptions(): HasMany  { return $this->hasMany(Prescription::class); }
}
