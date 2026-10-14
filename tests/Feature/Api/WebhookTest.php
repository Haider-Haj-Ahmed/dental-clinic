<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): array
    {
        $user  = User::factory()->owner()->create();
        $token = $user->createToken('test', ['*'])->plainTextToken;
        return [$user, $token];
    }

    public function test_owner_can_register_webhook(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $response = $this->withToken($token)
             ->postJson('/api/v1/webhooks', [
                 'url'    => 'https://example.com/webhook',
                 'events' => ['appointment.booked', 'invoice.finalized'],
             ])->assertCreated()
               ->assertJsonStructure(['data' => ['id', 'url', 'events', 'is_active', 'secret']]);

        // Secret should be shown once
        $this->assertNotEmpty($response->json('data.secret'));
    }

    public function test_secret_not_exposed_in_list(): void
    {
        [$user, $token] = $this->actingAsOwner();

        Webhook::create([
            'url'        => 'https://example.com/webhook',
            'secret'     => 'super-secret',
            'events'     => ['appointment.booked'],
            'is_active'  => true,
            'created_by' => $user->id,
        ]);

        $response = $this->withToken($token)
             ->getJson('/api/v1/webhooks')
             ->assertOk();

        $this->assertArrayNotHasKey('secret', $response->json('data.0'));
    }

    public function test_toggle_webhook_active_state(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $webhook = Webhook::create([
            'url'        => 'https://example.com/webhook',
            'secret'     => 'secret',
            'events'     => ['appointment.booked'],
            'is_active'  => true,
            'created_by' => $user->id,
        ]);

        $this->withToken($token)
             ->patchJson("/api/v1/webhooks/{$webhook->id}/toggle")
             ->assertOk()
             ->assertJsonPath('is_active', false);

        $this->assertFalse($webhook->fresh()->is_active);
    }

    public function test_delete_webhook(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $webhook = Webhook::create([
            'url'        => 'https://example.com/webhook',
            'secret'     => 'secret',
            'events'     => ['appointment.booked'],
            'is_active'  => true,
            'created_by' => $user->id,
        ]);

        $this->withToken($token)
             ->deleteJson("/api/v1/webhooks/{$webhook->id}")
             ->assertOk();

        $this->assertNull(Webhook::find($webhook->id));
    }

    public function test_non_owner_cannot_register_webhook(): void
    {
        $user  = User::factory()->provider()->create();
        $token = $user->createToken('test', ['*'])->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/webhooks', [
                 'url'    => 'https://example.com/webhook',
                 'events' => ['appointment.booked'],
             ])->assertForbidden();
    }

    public function test_invalid_event_name_is_rejected(): void
    {
        [$user, $token] = $this->actingAsOwner();

        $this->withToken($token)
             ->postJson('/api/v1/webhooks', [
                 'url'    => 'https://example.com/webhook',
                 'events' => ['invalid.event.name'],
             ])->assertStatus(422);
    }
}
