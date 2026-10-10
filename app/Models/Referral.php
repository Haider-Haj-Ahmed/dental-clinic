<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public const URGENCY_ROUTINE   = 'routine';
    public const URGENCY_URGENT    = 'urgent';
    public const URGENCY_EMERGENCY = 'emergency';

    protected $fillable = [
        'patient_id', 'referring_provider_id', 'encounter_id',
        'referred_to_name', 'referred_to_specialty', 'referred_to_clinic',
        'referred_to_email', 'referred_to_phone',
        'reason', 'clinical_notes', 'relevant_history', 'requested_action',
        'urgency', 'referral_date', 'appointment_date',
        'is_printed', 'printed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'referral_date'    => 'date',
            'appointment_date' => 'date',
            'is_printed'       => 'boolean',
            'printed_at'       => 'datetime',
        ];
    }

    public function patient(): BelongsTo           { return $this->belongsTo(Patient::class); }
    public function referringProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'referring_provider_id'); }
    public function encounter(): BelongsTo         { return $this->belongsTo(Encounter::class); }
    public function creator(): BelongsTo           { return $this->belongsTo(User::class, 'created_by'); }
}
