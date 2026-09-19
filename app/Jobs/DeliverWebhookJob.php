<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeliverWebhookJob
 *
 * Sends a signed HTTP POST to a registered webhook URL.
 * Payload is signed with HMAC-SHA256 using the webhook's secret.
 * Delivery attempt is logged in webhook_deliveries.
 * On failure: retries 3 times with exponential backoff (1min, 5min, 15min).
 * After all retries fail: marks delivery as 'failed' and deactivates
 * the webhook if it has failed 10 consecutive times.
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [60, 300, 900];   // 1min, 5min, 15min
    public int $timeout = 30;

    public function __construct(
        private readonly Webhook $webhook,
        private readonly string $event,
        private readonly array $payload,
        private readonly int $deliveryId,
    ) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        $body      = json_encode($this->payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $this->webhook->secret);
        $startMs   = now()->getPreciseTimestamp(3);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'             => 'application/json',
                    'X-Webhook-Event'          => $this->event,
                    'X-Webhook-Signature-256'  => $signature,
                    'X-Webhook-Delivery'       => $this->deliveryId,
                    'X-Webhook-Timestamp'      => now()->toIso8601String(),
                    'User-Agent'               => 'CrystallineDental-Webhooks/1.0',
                ])
                ->post($this->webhook->url, $this->payload);

            $elapsed = (int) (now()->getPreciseTimestamp(3) - $startMs);

            $success = $response->successful();

            $delivery->update([
                'attempt'          => $this->attempts(),
                'response_status'  => $response->status(),
                'response_body'    => substr($response->body(), 0, 1000),
                'response_time_ms' => $elapsed,
                'status'           => $success ? WebhookDelivery::STATUS_SUCCESS : WebhookDelivery::STATUS_FAILED,
                'error'            => $success ? null : "HTTP {$response->status()}",
                'delivered_at'     => $success ? now() : null,
            ]);

            $this->webhook->update(['last_triggered_at' => now()]);

            if (! $success) {
                $this->fail(new \Exception("Webhook delivery failed with HTTP {$response->status()}"));
            }

        } catch (\Throwable $e) {
            $elapsed = (int) (now()->getPreciseTimestamp(3) - $startMs);

            $delivery->update([
                'attempt'          => $this->attempts(),
                'response_time_ms' => $elapsed,
                'status'           => WebhookDelivery::STATUS_FAILED,
                'error'            => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning("DeliverWebhookJob permanently failed", [
            'webhook_id'  => $this->webhook->id,
            'event'       => $this->event,
            'delivery_id' => $this->deliveryId,
            'error'       => $e->getMessage(),
        ]);

        WebhookDelivery::where('id', $this->deliveryId)->update([
            'status' => WebhookDelivery::STATUS_FAILED,
        ]);

        // Auto-deactivate if 10 consecutive failures
        $recentFailures = $this->webhook->deliveries()
            ->where('status', WebhookDelivery::STATUS_FAILED)
            ->latest()
            ->limit(10)
            ->count();

        if ($recentFailures >= 10) {
            $this->webhook->update(['is_active' => false]);
            Log::warning("Webhook #{$this->webhook->id} auto-deactivated after 10 consecutive failures.");
        }
    }
}
