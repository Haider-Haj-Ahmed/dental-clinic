<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW   = 'no_show';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
    ];

    protected $fillable = [
        'patient_id',
        'provider_id',
        'appointment_type_id',
        'operatory_id',
        'start_at',
        'end_at',
        'status',
        'chief_complaint',
        'notes',
        'color',
        'reminder_sent_at',
        'created_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at'          => 'immutable_datetime',
            'end_at'            => 'immutable_datetime',
            'cancelled_at'      => 'immutable_datetime',
            'reminder_sent_at'  => 'immutable_datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function operatory(): BelongsTo
    {
        return $this->belongsTo(Operatory::class);
    }
}
