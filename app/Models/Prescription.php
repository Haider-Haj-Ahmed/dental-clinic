<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = [
        'patient_id', 'provider_id', 'encounter_id',
        'issued_at', 'notes', 'is_printed', 'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at'  => 'datetime',
            'printed_at' => 'datetime',
            'is_printed' => 'boolean',
        ];
    }

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo  { return $this->belongsTo(Provider::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function items(): HasMany       { return $this->hasMany(PrescriptionItem::class); }
}
