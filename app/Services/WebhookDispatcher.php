<?php

namespace App\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Webhook;
use App\Models\WebhookDelivery;

/**
 * WebhookDispatcher
 *
 * Central service for dispatching webhook events.
 * Called from event listeners after in-app notifications are sent.
 *
 * Usage:
 *   app(WebhookDispatcher::class)->dispatch('appointment.booked', [
 *       'appointment_id' => 42,
 *       'patient_name'   => 'Ahmad Khalil',
 *       ...
 *   ]);
 */
class WebhookDispatcher
{
    public function dispatch(string $event, array $payload): void
    {
        // Find all active webhooks subscribed to this event
        $webhooks = Webhook::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn ($webhook) => $webhook->subscribesTo($event));

        if ($webhooks->isEmpty()) {
            return;
        }

        // Standard envelope wrapping the payload
        $envelope = [
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'data'      => $payload,
        ];

        foreach ($webhooks as $webhook) {
            // Create a pending delivery log entry
            $delivery = WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'event'      => $event,
                'payload'    => $envelope,
                'attempt'    => 1,
                'status'     => WebhookDelivery::STATUS_PENDING,
            ]);

            // Dispatch the job to queue
            DeliverWebhookJob::dispatch($webhook, $event, $envelope, $delivery->id)
                ->onQueue('default');
        }
    }
}
