<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAllergy extends Model
{
    protected $fillable = ['patient_id', 'allergen', 'reaction', 'severity', 'noted_by', 'noted_at', 'notes'];

    protected function casts(): array
    {
        return ['noted_at' => 'datetime'];
    }

    public function patient(): BelongsTo  { return $this->belongsTo(Patient::class); }
    public function notedBy(): BelongsTo  { return $this->belongsTo(Provider::class, 'noted_by'); }
}
