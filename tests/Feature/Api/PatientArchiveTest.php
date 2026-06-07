<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_archive_patient(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_RECEPTIONIST]));
        $patient = Patient::factory()->create();

        $this->postJson("/api/v1/patients/{$patient->id}/archive")
            ->assertOk()
            ->assertJsonPath('message', 'Patient archived.');

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_receptionist_can_restore_patient(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_RECEPTIONIST]));
        $patient = Patient::factory()->create();
        $patient->delete();

        $this->postJson("/api/v1/patients/{$patient->id}/restore")
            ->assertOk();

        $this->assertNotSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_provider_cannot_archive_patient(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_PROVIDER]));
        $patient = Patient::factory()->create();

        $this->postJson("/api/v1/patients/{$patient->id}/archive")
            ->assertForbidden();
    }

    public function test_archived_patient_cannot_be_booked(): void
    {
        $receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        Sanctum::actingAs($receptionist);

        $patient  = Patient::factory()->create();
        $provider = \App\Models\Provider::factory()->create();

        $patient->delete(); // archive the patient

        $this->postJson('/api/v1/appointments', [
            'patient_id'  => $patient->id,
            'provider_id' => $provider->id,
            'start_at'    => '2026-07-01 10:00:00',
            'end_at'      => '2026-07-01 10:45:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('patient_id');
    }
}
