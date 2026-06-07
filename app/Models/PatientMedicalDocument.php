<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'xray',
        'panoramic',
        'cephalometric',
        'intraoral_photo',
        'lab_result',
        'referral_letter',
        'consent_form',
        'old_treatment_record',
        'prescription_scan',
        'other',
    ];

    protected $fillable = [
        'patient_id',
        'medical_case_id',   // nullable — doc may exist without a case
        'provider_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'file_size_kb',
        'mime_type',
        'taken_at',          // when the X-ray/photo was taken (may differ from upload date)
        'external_source',   // e.g. "Dr. Khalil – City Hospital 2021"
        'is_visible_to_patient',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'taken_at'              => 'date',
            'is_visible_to_patient' => 'boolean',
            'file_size_kb'          => 'integer',
        ];
    }

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function medicalCase(): BelongsTo { return $this->belongsTo(PatientMedicalCase::class, 'medical_case_id'); }
    public function provider(): BelongsTo    { return $this->belongsTo(Provider::class); }
    public function uploadedBy(): BelongsTo  { return $this->belongsTo(User::class, 'uploaded_by'); }
}
