<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\PatientMedicalCase;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientMedicalCaseTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private User $owner;
    private User $providerUser;
    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient      = Patient::factory()->create();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->provider     = Provider::factory()->create(['user_id' => $this->providerUser->id]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_owner_can_create_internal_case(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date'           => '2024-03-15',
            'case_type'           => 'root_canal',
            'chief_complaint'     => 'Severe toothache',
            'diagnosis'           => 'Pulp necrosis on tooth 36',
            'treatment_performed' => 'Root canal therapy performed',
            'outcome'             => 'Successful, patient discharged',
            'is_external'         => false,
        ])->assertCreated()
            ->assertJsonPath('data.case_type', 'root_canal')
            ->assertJsonPath('data.is_external', false)
            ->assertJsonPath('data.patient_id', $this->patient->id);
    }

    public function test_provider_auto_assigned_as_provider_id_when_omitted(): void
    {
        Sanctum::actingAs($this->providerUser);

        $response = $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date' => '2024-03-15',
            'case_type' => 'filling',
        ])->assertCreated();

        $this->assertEquals($this->provider->id, $response->json('data.provider_id'));
    }

    public function test_external_case_requires_previous_clinic(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date'   => '2022-01-01',
            'case_type'   => 'extraction',
            'is_external' => true,
            // previous_clinic intentionally omitted
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('previous_clinic');
    }

    public function test_external_case_records_previous_clinic_and_dentist(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date'        => '2021-06-01',
            'case_type'        => 'crown',
            'is_external'      => true,
            'previous_clinic'  => 'City Dental Center',
            'previous_dentist' => 'Dr. Khalil Mansour',
            'diagnosis'        => 'Fractured molar',
        ])->assertCreated()
            ->assertJsonPath('data.is_external', true)
            ->assertJsonPath('data.previous_clinic', 'City Dental Center')
            ->assertJsonPath('data.previous_dentist', 'Dr. Khalil Mansour');
    }

    public function test_invalid_case_type_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date' => '2024-01-01',
            'case_type' => 'laser_whitening',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('case_type');
    }

    public function test_future_case_date_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date' => now()->addYear()->toDateString(),
            'case_type' => 'filling',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('case_date');
    }

    public function test_assistant_cannot_create_case(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ASSISTANT]));

        $this->postJson("/api/v1/patients/{$this->patient->id}/medical-cases", [
            'case_date' => '2024-01-01',
            'case_type' => 'filling',
        ])->assertForbidden();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function test_index_returns_cases_for_patient(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalCase::factory()->count(3)->create([
            'patient_id'  => $this->patient->id,
            'created_by'  => $this->owner->id,
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/medical-cases")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_can_filter_by_case_type(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'case_type' => 'filling',   'created_by' => $this->owner->id]);
        PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'case_type' => 'root_canal','created_by' => $this->owner->id]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/medical-cases?case_type=filling")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.case_type', 'filling');
    }

    public function test_index_can_filter_external_cases(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'is_external' => true,  'previous_clinic' => 'Old Clinic', 'created_by' => $this->owner->id]);
        PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'is_external' => false, 'created_by' => $this->owner->id]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/medical-cases?is_external=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_external', true);
    }

    public function test_show_returns_documents_count(): void
    {
        Sanctum::actingAs($this->owner);

        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->owner->id,
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/medical-cases/{$case->id}")
            ->assertOk()
            ->assertJsonPath('data.documents_count', 0);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_provider_can_update_own_case(): void
    {
        Sanctum::actingAs($this->providerUser);

        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->providerUser->id,
        ]);

        $this->putJson("/api/v1/patients/{$this->patient->id}/medical-cases/{$case->id}", [
            'outcome' => 'Patient recovered fully.',
        ])->assertOk()
            ->assertJsonPath('data.outcome', 'Patient recovered fully.');
    }

    public function test_provider_cannot_update_another_providers_case(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($otherUser);

        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->providerUser->id,  // created by different provider
        ]);

        $this->putJson("/api/v1/patients/{$this->patient->id}/medical-cases/{$case->id}", [
            'outcome' => 'Trying to overwrite.',
        ])->assertForbidden();
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_provider_can_delete_own_case(): void
    {
        Sanctum::actingAs($this->providerUser);

        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->providerUser->id,
        ]);

        $this->deleteJson("/api/v1/patients/{$this->patient->id}/medical-cases/{$case->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('patient_medical_cases', ['id' => $case->id]);
    }

    public function test_provider_cannot_delete_another_providers_case(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($otherUser);

        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->providerUser->id,
        ]);

        $this->deleteJson("/api/v1/patients/{$this->patient->id}/medical-cases/{$case->id}")
            ->assertForbidden();
    }

    // ── Cross-patient guard ───────────────────────────────────────────────────

    public function test_cannot_access_case_belonging_to_different_patient(): void
    {
        Sanctum::actingAs($this->owner);

        $otherPatient = Patient::factory()->create();
        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $otherPatient->id,
            'created_by' => $this->owner->id,
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/medical-cases/{$case->id}")
            ->assertNotFound();
    }
}
