<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    /**
     * Supported event names — must match what WebhookDispatcher fires.
     */
    public const EVENTS = [
        'appointment.booked',
        'appointment.status_changed',
        'invoice.finalized',
        'payment.received',
        'patient.created',
        'ai.result_ready',
        'recall.overdue',
        'inventory.low_stock',
    ];

    protected $fillable = [
        'url',
        'secret',
        'events',
        'description',
        'is_active',
        'created_by',
    ];

    protected $hidden = ['secret'];  // never expose the signing secret in API responses

    protected function casts(): array
    {
        return [
            'events'             => 'array',
            'is_active'          => 'boolean',
            'last_triggered_at'  => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
