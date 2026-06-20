<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientSubResourceTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private User $owner;
    private User $provider;
    private User $assistant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient   = Patient::factory()->create();
        $this->owner     = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->provider  = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->assistant = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    public function test_owner_can_add_contact(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/contacts", [
            'label'        => 'emergency',
            'name'         => 'Sara Ahmed',
            'phone'        => '+96170000000',
            'relationship' => 'spouse',
            'is_emergency' => true,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Sara Ahmed')
            ->assertJsonPath('data.is_emergency', true);
    }

    public function test_provider_can_add_contact(): void
    {
        Sanctum::actingAs($this->provider);

        $this->postJson("/api/v1/patients/{$this->patient->id}/contacts", [
            'label' => 'parent',
            'name'  => 'Ali Ahmed',
        ])->assertCreated();
    }

    public function test_assistant_cannot_add_contact(): void
    {
        Sanctum::actingAs($this->assistant);

        $this->postJson("/api/v1/patients/{$this->patient->id}/contacts", [
            'label' => 'parent',
            'name'  => 'Ali Ahmed',
        ])->assertForbidden();
    }

    public function test_assistant_can_view_contacts(): void
    {
        Sanctum::actingAs($this->assistant);

        $this->getJson("/api/v1/patients/{$this->patient->id}/contacts")
            ->assertOk();
    }

    // ── Allergies ─────────────────────────────────────────────────────────────

    public function test_owner_can_add_allergy(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/allergies", [
            'allergen' => 'Penicillin',
            'reaction' => 'Rash',
            'severity' => 'moderate',
        ])->assertCreated()
            ->assertJsonPath('data.allergen', 'Penicillin')
            ->assertJsonPath('data.severity', 'moderate');
    }

    public function test_invalid_severity_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/allergies", [
            'allergen' => 'Penicillin',
            'severity' => 'extreme',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('severity');
    }

    // ── Conditions ────────────────────────────────────────────────────────────

    public function test_owner_can_add_condition(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/conditions", [
            'condition'  => 'Type 2 Diabetes',
            'icd_code'   => 'E11',
            'status'     => 'active',
            'onset_date' => '2020-01-01',
        ])->assertCreated()
            ->assertJsonPath('data.condition', 'Type 2 Diabetes')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_conditions_can_be_filtered_by_status(): void
    {
        Sanctum::actingAs($this->owner);
        $this->patient->conditions()->createMany([
            ['condition' => 'Diabetes',   'status' => 'active'],
            ['condition' => 'Old Cavity', 'status' => 'resolved'],
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/conditions?status=active")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.condition', 'Diabetes');
    }

    // ── Medications ───────────────────────────────────────────────────────────

    public function test_owner_can_add_medication(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medications", [
            'drug_name'  => 'Metformin',
            'dose'       => '500mg',
            'frequency'  => 'twice daily',
            'start_date' => '2023-01-01',
        ])->assertCreated()
            ->assertJsonPath('data.drug_name', 'Metformin');
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/medications", [
            'drug_name'  => 'Metformin',
            'start_date' => '2023-06-01',
            'end_date'   => '2023-01-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('end_date');
    }

    // ── Consents ──────────────────────────────────────────────────────────────

    public function test_owner_can_add_consent(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/consents", [
            'consent_type'      => 'Treatment Consent',
            'signed_at'         => now()->toDateTimeString(),
            'signed_by_patient' => true,
        ])->assertCreated()
            ->assertJsonPath('data.consent_type', 'Treatment Consent')
            ->assertJsonPath('data.signed_by_patient', true);
    }

    // ── Cross-patient access guard ────────────────────────────────────────────

    public function test_cannot_access_contact_belonging_to_different_patient(): void
    {
        Sanctum::actingAs($this->owner);

        $otherPatient = Patient::factory()->create();
        $contact = $otherPatient->contacts()->create(['label' => 'other', 'name' => 'Test']);

        // Try to access via wrong patient route
        $this->getJson("/api/v1/patients/{$this->patient->id}/contacts/{$contact->id}")
            ->assertNotFound();
    }
}
