<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $receptionist;
    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->provider     = User::factory()->create(['role' => User::ROLE_PROVIDER]);
    }

    // ── Observer fires ────────────────────────────────────────────────────────

    public function test_creating_patient_writes_audit_log(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Ahmad',
            'last_name'  => 'Khalil',
            'phone'      => '+96170000001',
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => AuditLog::ACTION_CREATED,
            'model_type' => Patient::class,
            'user_id'    => $this->owner->id,
        ]);
    }

    public function test_updating_patient_writes_audit_log_with_diff(): void
    {
        Sanctum::actingAs($this->owner);

        $patient = Patient::factory()->create(['phone' => '+96170000001']);

        $this->putJson("/api/v1/patients/{$patient->id}", [
            'phone' => '+96170000002',
        ])->assertOk();

        $log = AuditLog::where('action', AuditLog::ACTION_UPDATED)
            ->where('model_type', Patient::class)
            ->where('model_id', $patient->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('+96170000001', $log->old_values['phone']);
        $this->assertEquals('+96170000002', $log->new_values['phone']);
    }

    public function test_deleting_patient_writes_audit_log(): void
    {
        Sanctum::actingAs($this->owner);

        $patient = Patient::factory()->create();

        $this->deleteJson("/api/v1/patients/{$patient->id}")->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => AuditLog::ACTION_DELETED,
            'model_type' => Patient::class,
            'model_id'   => $patient->id,
        ]);
    }

    public function test_restoring_patient_writes_audit_log(): void
    {
        Sanctum::actingAs($this->owner);

        $patient = Patient::factory()->create();
        $patient->delete();

        $this->postJson("/api/v1/patients/{$patient->id}/restore")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => AuditLog::ACTION_RESTORED,
            'model_type' => Patient::class,
            'model_id'   => $patient->id,
        ]);
    }

    public function test_password_field_excluded_from_audit_log(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/v1/users', [
            'name'     => 'Test User',
            'email'    => 'test@clinic.local',
            'password' => 'secret123',
            'role'     => User::ROLE_RECEPTIONIST,
        ])->assertCreated();

        $log = AuditLog::where('action', AuditLog::ACTION_CREATED)
            ->where('model_type', User::class)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    // ── API access control ────────────────────────────────────────────────────

    public function test_owner_can_list_audit_logs(): void
    {
        Sanctum::actingAs($this->owner);

        AuditLog::factory()->count(3)->create();

        $this->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_receptionist_cannot_view_audit_logs(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    public function test_provider_cannot_view_audit_logs(): void
    {
        Sanctum::actingAs($this->provider);

        $this->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    // ── Filters ───────────────────────────────────────────────────────────────

    public function test_can_filter_by_action(): void
    {
        Sanctum::actingAs($this->owner);

        AuditLog::factory()->create(['action' => AuditLog::ACTION_CREATED]);
        AuditLog::factory()->create(['action' => AuditLog::ACTION_UPDATED]);

        $this->getJson('/api/v1/audit-logs?action=created')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'created');
    }

    public function test_can_filter_by_model(): void
    {
        Sanctum::actingAs($this->owner);

        AuditLog::factory()->create(['model_type' => Patient::class]);
        AuditLog::factory()->create(['model_type' => User::class]);

        $this->getJson('/api/v1/audit-logs?model=Patient')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model_type', 'Patient');
    }

    public function test_can_filter_by_user_id(): void
    {
        Sanctum::actingAs($this->owner);

        AuditLog::factory()->create(['user_id' => $this->owner->id]);
        AuditLog::factory()->create(['user_id' => $this->receptionist->id]);

        $this->getJson("/api/v1/audit-logs?user_id={$this->owner->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_date_range(): void
    {
        Sanctum::actingAs($this->owner);

        AuditLog::factory()->create(['created_at' => now()->subDays(10)]);
        AuditLog::factory()->create(['created_at' => now()]);

        $this->getJson('/api/v1/audit-logs?date_from='.today()->toDateString().'&date_to='.today()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_model_id(): void
    {
        Sanctum::actingAs($this->owner);

        $patient = Patient::factory()->create();

        AuditLog::factory()->create(['model_type' => Patient::class, 'model_id' => $patient->id]);
        AuditLog::factory()->create(['model_type' => Patient::class, 'model_id' => 9999]);

        $this->getJson("/api/v1/audit-logs?model=Patient&model_id={$patient->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_returns_single_log(): void
    {
        Sanctum::actingAs($this->owner);

        $log = AuditLog::factory()->create(['user_id' => $this->owner->id]);

        $this->getJson("/api/v1/audit-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $log->id);
    }

    public function test_invalid_action_filter_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/audit-logs?action=hacked')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');
    }
}
