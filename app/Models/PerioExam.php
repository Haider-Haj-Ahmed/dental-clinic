<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerioExam extends Model
{
    protected $fillable = ['patient_id', 'provider_id', 'exam_date', 'notes'];

    protected function casts(): array
    {
        return ['exam_date' => 'date'];
    }

    public function patient(): BelongsTo  { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
    public function measures(): HasMany   { return $this->hasMany(PerioMeasure::class); }
}
