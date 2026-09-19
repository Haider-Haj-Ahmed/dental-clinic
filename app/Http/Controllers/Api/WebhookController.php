<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * WebhookController
 *
 * Owner-only. Manages webhook registrations and delivery history.
 *
 * Routes:
 *   GET    /api/v1/webhooks                         → index
 *   POST   /api/v1/webhooks                         → store
 *   GET    /api/v1/webhooks/{webhook}               → show
 *   PATCH  /api/v1/webhooks/{webhook}/toggle        → toggle (activate/deactivate)
 *   DELETE /api/v1/webhooks/{webhook}               → destroy
 *   GET    /api/v1/webhooks/{webhook}/deliveries    → deliveries
 *   POST   /api/v1/webhooks/{webhook}/ping          → ping (test delivery)
 */
class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $webhooks = Webhook::query()
            ->withCount(['deliveries', 'deliveries as failed_deliveries_count' => fn ($q) => $q->where('status', 'failed')])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($w) => $this->format($w));

        return response()->json([
            'data'           => $webhooks,
            'supported_events' => Webhook::EVENTS,
        ]);
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = Webhook::create([
            'url'         => $request->validated('url'),
            'events'      => $request->validated('events'),
            'description' => $request->validated('description'),
            'secret'      => Str::random(40),  // auto-generated, shown once
            'is_active'   => true,
            'created_by'  => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Webhook registered successfully.',
            'data'    => array_merge($this->format($webhook), [
                'secret' => $webhook->secret,  // shown ONCE at creation
                'note'   => 'Store this secret securely. It will not be shown again.',
            ]),
        ], 201);
    }

    public function show(Request $request, Webhook $webhook): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $webhook->loadCount(['deliveries', 'deliveries as failed_deliveries_count' => fn ($q) => $q->where('status', 'failed')]);

        return response()->json(['data' => $this->format($webhook)]);
    }

    public function toggle(Request $request, Webhook $webhook): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $webhook->update(['is_active' => ! $webhook->is_active]);

        return response()->json([
            'message'   => $webhook->is_active ? 'Webhook activated.' : 'Webhook deactivated.',
            'is_active' => $webhook->is_active,
        ]);
    }

    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted.']);
    }

    /**
     * List delivery history for a specific webhook.
     */
    public function deliveries(Request $request, Webhook $webhook): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $deliveries = $webhook->deliveries()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $deliveries->map(fn ($d) => [
                'id'               => $d->id,
                'event'            => $d->event,
                'attempt'          => $d->attempt,
                'status'           => $d->status,
                'response_status'  => $d->response_status,
                'response_time_ms' => $d->response_time_ms,
                'error'            => $d->error,
                'delivered_at'     => $d->delivered_at?->toIso8601String(),
                'created_at'       => $d->created_at->toIso8601String(),
            ]),
            'total'        => $deliveries->total(),
            'current_page' => $deliveries->currentPage(),
            'last_page'    => $deliveries->lastPage(),
        ]);
    }

    /**
     * Send a test ping to the webhook URL to verify it's reachable.
     */
    public function ping(Request $request, Webhook $webhook): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $payload = [
            'event'     => 'ping',
            'timestamp' => now()->toIso8601String(),
            'data'      => [
                'message'    => 'This is a test delivery from Crystalline Dental PMS.',
                'webhook_id' => $webhook->id,
            ],
        ];

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event'      => 'ping',
            'payload'    => $payload,
            'attempt'    => 1,
            'status'     => WebhookDelivery::STATUS_PENDING,
        ]);

        \App\Jobs\DeliverWebhookJob::dispatch($webhook, 'ping', $payload, $delivery->id);

        return response()->json([
            'message'     => 'Test ping dispatched to queue.',
            'delivery_id' => $delivery->id,
        ]);
    }

    private function format(Webhook $webhook): array
    {
        return [
            'id'                    => $webhook->id,
            'url'                   => $webhook->url,
            'events'                => $webhook->events,
            'description'           => $webhook->description,
            'is_active'             => $webhook->is_active,
            'last_triggered_at'     => $webhook->last_triggered_at?->toIso8601String(),
            'deliveries_count'      => $webhook->deliveries_count ?? null,
            'failed_deliveries_count' => $webhook->failed_deliveries_count ?? null,
            'created_at'            => $webhook->created_at->toIso8601String(),
        ];
    }
}
