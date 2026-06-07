<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active'     => 'boolean',
        ];
    }

    // Core
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // Medical sub-records
    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientCondition::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    // Medical history
    public function medicalCases(): HasMany
    {
        return $this->hasMany(PatientMedicalCase::class);
    }

    public function medicalDocuments(): HasMany
    {
        return $this->hasMany(PatientMedicalDocument::class);
    }

    // Clinical
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function odontogramEntries(): HasMany
    {
        return $this->hasMany(OdontogramEntry::class);
    }

    public function perioExams(): HasMany
    {
        return $this->hasMany(PerioExam::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function recalls(): HasMany
    {
        return $this->hasMany(Recall::class);
    }
}
