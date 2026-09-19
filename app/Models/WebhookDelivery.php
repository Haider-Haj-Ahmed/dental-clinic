<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'attempt',
        'response_status',
        'response_body',
        'response_time_ms',
        'status',
        'error',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'       => 'array',
            'delivered_at'  => 'datetime',
        ];
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
