<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontogramEntry extends Model
{
    public const ENTRY_TYPE_CONDITION = 'condition';
    public const ENTRY_TYPE_PROCEDURE = 'procedure';

    protected $fillable = [
        'encounter_id', 'patient_id', 'provider_id',
        'tooth_number', 'surface', 'entry_type',
        'code', 'color_hex', 'notes', 'recorded_at',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo  { return $this->belongsTo(Provider::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
}
