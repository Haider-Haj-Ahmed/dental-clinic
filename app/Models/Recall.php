<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recall extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_SENT     = 'sent';
    public const STATUS_BOOKED   = 'booked';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'patient_id', 'due_date', 'status',
        'last_reminder_sent_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date'              => 'date',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
}
