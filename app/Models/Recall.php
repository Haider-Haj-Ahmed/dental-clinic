<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recall extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_BOOKED    = 'booked';
    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SENT,
        self::STATUS_BOOKED,
        self::STATUS_DISMISSED,
    ];

    protected $fillable = [
        'patient_id',
        'due_date',
        'status',
        'last_reminder_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date'              => 'immutable_date',
            'last_reminder_sent_at' => 'immutable_datetime',
        ];
    }

    public function patient(): BelongsTo          { return $this->belongsTo(Patient::class); }
    public function communicationLogs(): HasMany   { return $this->hasMany(CommunicationLog::class); }
}
