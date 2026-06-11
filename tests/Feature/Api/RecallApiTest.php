<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecallApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $receptionist;
    private User $provider;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->provider     = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->patient      = Patient::factory()->create();
    }

    public function test_receptionist_can_create_recall(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->postJson('/api/v1/recalls', [
            'patient_id' => $this->patient->id,
            'due_date'   => now()->addMonths(6)->toDateString(),
            'notes'      => '6-month cleaning due',
        ])->assertCreated()
            ->assertJsonPath('data.status', Recall::STATUS_PENDING)
            ->assertJsonPath('data.patient_id', $this->patient->id);
    }

    public function test_provider_cannot_create_recall(): void
    {
        Sanctum::actingAs($this->provider);

        $this->postJson('/api/v1/recalls', [
            'patient_id' => $this->patient->id,
            'due_date'   => now()->addMonths(6)->toDateString(),
        ])->assertForbidden();
    }

    public function test_archived_patient_cannot_have_recall(): void
    {
        Sanctum::actingAs($this->receptionist);
        $this->patient->delete();

        $this->postJson('/api/v1/recalls', [
            'patient_id' => $this->patient->id,
            'due_date'   => now()->addMonths(3)->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('patient_id');
    }

    public function test_due_endpoint_returns_only_overdue_recalls(): void
    {
        Sanctum::actingAs($this->owner);

        Recall::factory()->create(['patient_id' => $this->patient->id, 'due_date' => now()->subDay(),  'status' => Recall::STATUS_PENDING]);
        Recall::factory()->create(['patient_id' => $this->patient->id, 'due_date' => now()->addMonth(), 'status' => Recall::STATUS_PENDING]);
        Recall::factory()->create(['patient_id' => $this->patient->id, 'due_date' => now()->subDays(5), 'status' => Recall::STATUS_DISMISSED]);

        $this->getJson('/api/v1/recalls/due')
            ->assertOk()
            ->assertJsonCount(1, 'data'); // only pending/sent that are overdue
    }

    public function test_status_can_be_updated(): void
    {
        Sanctum::actingAs($this->receptionist);
        $recall = Recall::factory()->create(['patient_id' => $this->patient->id, 'status' => Recall::STATUS_PENDING]);

        $this->patchJson("/api/v1/recalls/{$recall->id}/status", [
            'status' => Recall::STATUS_BOOKED,
        ])->assertOk()
            ->assertJsonPath('data.status', Recall::STATUS_BOOKED);
    }

    public function test_invalid_status_rejected(): void
    {
        Sanctum::actingAs($this->receptionist);
        $recall = Recall::factory()->create(['patient_id' => $this->patient->id]);

        $this->patchJson("/api/v1/recalls/{$recall->id}/status", [
            'status' => 'expired',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_send_reminder_queues_communication_log_and_updates_recall(): void
    {
        Sanctum::actingAs($this->receptionist);
        $recall = Recall::factory()->create([
            'patient_id' => $this->patient->id,
            'status'     => Recall::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/recalls/{$recall->id}/send-reminder")
            ->assertOk()
            ->assertJsonPath('message', 'Reminder queued successfully.');

        $this->assertDatabaseHas('communication_logs', [
            'patient_id' => $this->patient->id,
            'recall_id'  => null, // logged via recall's communicationLogs relation — patient_id is set
            'status'     => 'queued',
        ]);

        $this->assertDatabaseHas('recalls', [
            'id'     => $recall->id,
            'status' => Recall::STATUS_SENT,
        ]);
    }

    public function test_index_can_filter_by_status(): void
    {
        Sanctum::actingAs($this->owner);

        Recall::factory()->create(['patient_id' => $this->patient->id, 'status' => Recall::STATUS_PENDING]);
        Recall::factory()->create(['patient_id' => $this->patient->id, 'status' => Recall::STATUS_BOOKED]);

        $this->getJson('/api/v1/recalls?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', Recall::STATUS_PENDING);
    }

    public function test_owner_can_delete_recall(): void
    {
        Sanctum::actingAs($this->owner);
        $recall = Recall::factory()->create(['patient_id' => $this->patient->id]);

        $this->deleteJson("/api/v1/recalls/{$recall->id}")->assertNoContent();
        $this->assertDatabaseMissing('recalls', ['id' => $recall->id]);
    }

    public function test_receptionist_cannot_delete_recall(): void
    {
        Sanctum::actingAs($this->receptionist);
        $recall = Recall::factory()->create(['patient_id' => $this->patient->id]);

        $this->deleteJson("/api/v1/recalls/{$recall->id}")->assertForbidden();
    }
}
