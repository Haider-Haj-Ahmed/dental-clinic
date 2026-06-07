<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientMedicalCase extends Model
{
    use HasFactory;

    public const TYPES = [
        'examination', 'extraction', 'filling', 'root_canal',
        'crown', 'implant', 'orthodontics', 'periodontal',
        'surgery', 'consultation', 'other',
    ];

    protected $fillable = [
        'patient_id',
        'provider_id',
        'previous_clinic',
        'previous_dentist',
        'case_date',
        'case_type',
        'chief_complaint',
        'diagnosis',
        'treatment_performed',
        'outcome',
        'is_external',   // true = imported from another clinic
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'case_date'   => 'date',
            'is_external' => 'boolean',
        ];
    }

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo  { return $this->belongsTo(Provider::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientMedicalDocument::class, 'medical_case_id');
    }
}
