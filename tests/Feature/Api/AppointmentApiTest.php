<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_create_appointment(): void
    {
        $receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        Sanctum::actingAs($receptionist, ['*']);

        $patient = Patient::factory()->create();
        $provider = Provider::factory()->create();

        $this->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'start_at' => '2026-06-01 10:00:00',
            'end_at' => '2026-06-01 10:45:00',
            'chief_complaint' => 'Tooth pain',
        ])->assertCreated()
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.provider_id', $provider->id);
    }

    public function test_provider_only_sees_own_appointments(): void
    {
        $providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $provider = Provider::factory()->create(['user_id' => $providerUser->id]);
        $otherProvider = Provider::factory()->create();
        $patient = Patient::factory()->create();
        $creator = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);

        Appointment::factory()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'created_by' => $creator->id,
        ]);

        Appointment::factory()->create([
            'provider_id' => $otherProvider->id,
            'patient_id' => $patient->id,
            'created_by' => $creator->id,
        ]);

        Sanctum::actingAs($providerUser, ['*']);

        $this->getJson('/api/v1/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_appointment_overlap_is_rejected(): void
    {
        $receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        Sanctum::actingAs($receptionist, ['*']);

        $provider = Provider::factory()->create();
        $patientA = Patient::factory()->create();
        $patientB = Patient::factory()->create();

        Appointment::factory()->create([
            'provider_id' => $provider->id,
            'patient_id' => $patientA->id,
            'start_at' => '2026-06-10 09:00:00',
            'end_at' => '2026-06-10 10:00:00',
            'created_by' => $receptionist->id,
        ]);

        $this->postJson('/api/v1/appointments', [
            'provider_id' => $provider->id,
            'patient_id' => $patientB->id,
            'start_at' => '2026-06-10 09:30:00',
            'end_at' => '2026-06-10 10:30:00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('provider_id');
    }
}
