<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    // ── Token ability enforcement ─────────────────────────────────────────────

    public function test_provider_token_cannot_write_patients(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        // Provider token lacks patients:write
        Sanctum::actingAs($user, $user->tokenAbilities());

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Test',
            'last_name'  => 'Patient',
        ])->assertForbidden();
    }

    public function test_provider_token_can_read_patients(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($user, $user->tokenAbilities());

        $this->getJson('/api/v1/patients')->assertOk();
    }

    public function test_assistant_token_cannot_write_recalls(): void
    {
        $user    = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
        $patient = Patient::factory()->create();
        Sanctum::actingAs($user, $user->tokenAbilities());

        $this->postJson('/api/v1/recalls', [
            'patient_id' => $patient->id,
            'due_date'   => now()->addMonth()->toDateString(),
        ])->assertForbidden();
    }

    public function test_assistant_token_cannot_write_billing(): void
    {
        $user    = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
        $patient = Patient::factory()->create();
        Sanctum::actingAs($user, $user->tokenAbilities());

        $this->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
            'issued_at'  => today()->toDateString(),
            'items'      => [['description' => 'Test', 'qty' => 1, 'unit_price' => 100]],
        ])->assertForbidden();
    }

    public function test_assistant_token_cannot_read_inventory(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
        Sanctum::actingAs($user, $user->tokenAbilities());

        // Assistant has no inventory:read ability
        $this->getJson('/api/v1/inventory-items')->assertForbidden();
    }

    public function test_receptionist_token_can_write_recalls(): void
    {
        $user    = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $patient = Patient::factory()->create();
        Sanctum::actingAs($user, $user->tokenAbilities());

        $this->postJson('/api/v1/recalls', [
            'patient_id' => $patient->id,
            'due_date'   => now()->addMonth()->toDateString(),
        ])->assertCreated();
    }

    public function test_receptionist_can_write_inventory(): void
    {
        // Receptionist has inventory:write — they manage stock and purchase orders
        $user      = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $abilities = $user->tokenAbilities();

        $this->assertContains('inventory:write', $abilities);
    }

    public function test_owner_token_bypasses_all_ability_checks(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        Sanctum::actingAs($user, $user->tokenAbilities());

        // Owner gets '*' — can do everything
        $this->getJson('/api/v1/patients')->assertOk();
        $this->getJson('/api/v1/inventory-items')->assertOk();
        $this->getJson('/api/v1/audit-logs')->assertOk();
    }

    // ── tokenAbilities() completeness ─────────────────────────────────────────

    public function test_owner_gets_wildcard_ability(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->assertEquals(['*'], $user->tokenAbilities());
    }

    public function test_provider_has_expected_abilities(): void
    {
        $user      = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $abilities = $user->tokenAbilities();

        $this->assertContains('patients:read',       $abilities);
        $this->assertContains('appointments:read',   $abilities);
        $this->assertContains('appointments:write',  $abilities);
        $this->assertContains('clinical:read',       $abilities);
        $this->assertContains('clinical:write',      $abilities);
        $this->assertContains('documents:write',     $abilities);
        $this->assertNotContains('patients:write',   $abilities);
        $this->assertNotContains('billing:write',    $abilities);
        $this->assertNotContains('inventory:read',   $abilities);
    }

    public function test_receptionist_has_expected_abilities(): void
    {
        $user      = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $abilities = $user->tokenAbilities();

        $this->assertContains('patients:read',         $abilities);
        $this->assertContains('patients:write',        $abilities);
        $this->assertContains('appointments:read',     $abilities);
        $this->assertContains('appointments:write',    $abilities);
        $this->assertContains('billing:read',          $abilities);
        $this->assertContains('billing:write',         $abilities);
        $this->assertContains('recalls:read',          $abilities);
        $this->assertContains('recalls:write',         $abilities);
        $this->assertContains('inventory:read',        $abilities);
        $this->assertContains('inventory:read',        $abilities);
        $this->assertNotContains('*',                  $abilities);
    }

    public function test_assistant_has_expected_abilities(): void
    {
        $user      = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
        $abilities = $user->tokenAbilities();

        $this->assertContains('patients:read',          $abilities);
        $this->assertContains('appointments:read',      $abilities);
        $this->assertContains('documents:write',        $abilities);
        $this->assertNotContains('patients:write',      $abilities);
        $this->assertNotContains('appointments:write',  $abilities);
        $this->assertNotContains('recalls:read',        $abilities);
        $this->assertNotContains('billing:read',        $abilities);
        $this->assertNotContains('inventory:read',      $abilities);
    }

    // ── Rate limiter config ───────────────────────────────────────────────────

    public function test_rate_limiters_are_registered(): void
    {
        $this->assertTrue(RateLimiter::has('api'));
        $this->assertTrue(RateLimiter::has('auth'));
    }

    // ── Sanctum token expiry ──────────────────────────────────────────────────

    public function test_sanctum_token_expiry_is_configured(): void
    {
        $expiry = config('sanctum.expiration');
        $this->assertNotNull($expiry, 'Sanctum token expiry should be set.');
        $this->assertGreaterThan(0, $expiry);
    }

    // ── per_page guard ────────────────────────────────────────────────────────

    public function test_per_page_is_clamped_to_100(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        Sanctum::actingAs($user, ['*']);

        Patient::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/patients?per_page=100000')
            ->assertOk();

        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }
}
