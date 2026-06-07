<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContact extends Model
{
    protected $fillable = ['patient_id', 'label', 'name', 'phone', 'relationship', 'is_emergency'];

    protected function casts(): array
    {
        return ['is_emergency' => 'boolean'];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
}
