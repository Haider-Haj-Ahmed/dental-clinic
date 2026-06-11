<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    use HasFactory;

    public const CHANNEL_EMAIL    = 'email';
    public const CHANNEL_SMS      = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNELS = [
        self::CHANNEL_EMAIL,
        self::CHANNEL_SMS,
        self::CHANNEL_WHATSAPP,
    ];

    public const DIRECTION_OUT = 'out';
    public const DIRECTION_IN  = 'in';

    public const DIRECTIONS = [
        self::DIRECTION_OUT,
        self::DIRECTION_IN,
    ];

    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENT      = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED    = 'failed';

    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_SENT,
        self::STATUS_DELIVERED,
        self::STATUS_FAILED,
    ];

    protected $fillable = [
        'patient_id',
        'provider_id',
        'recall_id',
        'channel',
        'direction',
        'subject',
        'body_preview',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo  { return $this->belongsTo(Patient::class); }
    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
    public function recall(): BelongsTo   { return $this->belongsTo(Recall::class); }
}
