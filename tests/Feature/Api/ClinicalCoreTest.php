<?php

namespace Tests\Feature\Api;

use App\Models\Encounter;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use App\Models\PerioExam;
use App\Models\PerioMeasure;
use App\Models\Prescription;
use App\Models\Provider;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicalCoreTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $providerUser;
    private Provider $provider;
    private User $receptionist;
    private User $assistant;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->provider     = Provider::factory()->create(['user_id' => $this->providerUser->id]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->assistant    = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
        $this->patient      = Patient::factory()->create();
    }

    // ── Encounter CRUD ────────────────────────────────────────────────────────

    public function test_provider_can_create_encounter(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson('/api/v1/encounters', [
            'patient_id'     => $this->patient->id,
            'provider_id'    => $this->provider->id,
            'encounter_date' => today()->toDateString(),
            'subjective'     => 'Patient reports tooth pain upper right',
            'objective'      => 'Tooth 16 tender to percussion',
        ])->assertCreated()
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.is_locked', false);
    }

    public function test_receptionist_cannot_create_encounter(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson('/api/v1/encounters', [
            'patient_id'     => $this->patient->id,
            'provider_id'    => $this->provider->id,
            'encounter_date' => today()->toDateString(),
        ])->assertForbidden();
    }

    public function test_assistant_can_read_encounters(): void
    {
        Sanctum::actingAs($this->assistant, $this->assistant->tokenAbilities());

        $this->getJson('/api/v1/encounters')->assertOk();
    }

    public function test_encounter_can_be_updated_when_unlocked(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'is_locked'   => false,
        ]);

        $this->putJson("/api/v1/encounters/{$encounter->id}", [
            'assessment' => 'Irreversible pulpitis tooth 16',
            'plan'       => 'Root canal therapy recommended',
        ])->assertOk()
            ->assertJsonPath('data.assessment', 'Irreversible pulpitis tooth 16');
    }

    public function test_locked_encounter_cannot_be_updated(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'is_locked'   => true,
        ]);

        $this->putJson("/api/v1/encounters/{$encounter->id}", [
            'assessment' => 'Trying to edit locked encounter',
        ])->assertUnprocessable();
    }

    public function test_provider_can_lock_and_unlock_encounter(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'is_locked'   => false,
        ]);

        $this->postJson("/api/v1/encounters/{$encounter->id}/lock")
            ->assertOk()
            ->assertJsonPath('data.is_locked', true);

        $this->postJson("/api/v1/encounters/{$encounter->id}/unlock")
            ->assertOk()
            ->assertJsonPath('data.is_locked', false);
    }

    public function test_future_encounter_date_rejected(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson('/api/v1/encounters', [
            'patient_id'     => $this->patient->id,
            'provider_id'    => $this->provider->id,
            'encounter_date' => now()->addDay()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('encounter_date');
    }

    // ── Odontogram ────────────────────────────────────────────────────────────

    public function test_provider_can_add_odontogram_entry(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'is_locked'   => false,
        ]);

        $this->postJson("/api/v1/encounters/{$encounter->id}/odontogram-entries", [
            'tooth_number' => 36,
            'surface'      => 'O',
            'entry_type'   => OdontogramEntry::ENTRY_TYPE_CONDITION,
            'code'         => 'CARIES',
            'color_hex'    => '#FF0000',
            'notes'        => 'Occlusal caries detected',
        ])->assertCreated()
            ->assertJsonPath('data.tooth_number', 36)
            ->assertJsonPath('data.entry_type', OdontogramEntry::ENTRY_TYPE_CONDITION);
    }

    public function test_cannot_add_odontogram_entry_to_locked_encounter(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'is_locked'   => true,
        ]);

        $this->postJson("/api/v1/encounters/{$encounter->id}/odontogram-entries", [
            'tooth_number' => 36,
            'entry_type'   => OdontogramEntry::ENTRY_TYPE_CONDITION,
        ])->assertUnprocessable();
    }

    public function test_patient_full_mouth_odontogram_view(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
        ]);

        OdontogramEntry::create([
            'encounter_id' => $encounter->id,
            'patient_id'   => $this->patient->id,
            'provider_id'  => $this->provider->id,
            'tooth_number' => 16,
            'entry_type'   => OdontogramEntry::ENTRY_TYPE_CONDITION,
            'recorded_at'  => now(),
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/odontogram")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tooth_number', 16);
    }

    public function test_invalid_tooth_number_rejected(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $encounter = Encounter::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'is_locked'   => false,
        ]);

        $this->postJson("/api/v1/encounters/{$encounter->id}/odontogram-entries", [
            'tooth_number' => 99,
            'entry_type'   => OdontogramEntry::ENTRY_TYPE_CONDITION,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tooth_number');
    }

    // ── Perio exams ───────────────────────────────────────────────────────────

    public function test_provider_can_create_perio_exam_with_measures(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson('/api/v1/perio-exams', [
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'exam_date'   => today()->toDateString(),
            'notes'       => 'Full mouth perio chart',
            'measures'    => [
                ['tooth_number' => 16, 'site' => 'MB', 'probing_depth' => 3, 'bleeding_on_probe' => false],
                ['tooth_number' => 16, 'site' => 'B',  'probing_depth' => 4, 'bleeding_on_probe' => true],
                ['tooth_number' => 16, 'site' => 'DB', 'probing_depth' => 5, 'bleeding_on_probe' => true],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.measures_count', 3);
    }

    public function test_perio_measure_can_be_added_after_exam_creation(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $exam = PerioExam::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'exam_date'   => today(),
        ]);

        $this->postJson("/api/v1/perio-exams/{$exam->id}/measures", [
            'tooth_number'      => 36,
            'site'              => 'ML',
            'probing_depth'     => 6,
            'bleeding_on_probe' => true,
            'furcation'         => 2,
        ])->assertCreated()
            ->assertJsonPath('data.tooth_number', 36)
            ->assertJsonPath('data.furcation', 2);
    }

    public function test_invalid_perio_site_rejected(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $exam = PerioExam::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'exam_date'   => today(),
        ]);

        $this->postJson("/api/v1/perio-exams/{$exam->id}/measures", [
            'tooth_number' => 36,
            'site'         => 'BUCCAL',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('site');
    }

    // ── Treatment plans ───────────────────────────────────────────────────────

    public function test_provider_can_create_treatment_plan_with_items(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson('/api/v1/treatment-plans', [
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'title'       => 'Comprehensive treatment plan',
            'items'       => [
                ['description' => 'Root canal tooth 16', 'fee' => 350.00],
                ['description' => 'Crown tooth 16',      'fee' => 450.00],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', TreatmentPlan::STATUS_DRAFT)
            ->assertJsonPath('data.total_fee', '800.00')
            ->assertJsonCount(2, 'data.items');
    }

    public function test_treatment_plan_status_workflow(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $plan = TreatmentPlan::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'title'       => 'Test plan',
            'status'      => TreatmentPlan::STATUS_DRAFT,
            'created_by'  => $this->providerUser->id,
        ]);
        $plan->items()->create(['description' => 'Filling', 'fee' => 100]);
        $plan->update(['total_fee' => 100]);

        // Draft → present
        $this->postJson("/api/v1/treatment-plans/{$plan->id}/present")
            ->assertOk()
            ->assertJsonPath('data.status', TreatmentPlan::STATUS_PRESENTED);

        // Present → accept
        $this->postJson("/api/v1/treatment-plans/{$plan->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', TreatmentPlan::STATUS_ACCEPTED);
    }

    public function test_cannot_present_empty_treatment_plan(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $plan = TreatmentPlan::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'title'       => 'Empty plan',
            'status'      => TreatmentPlan::STATUS_DRAFT,
            'created_by'  => $this->providerUser->id,
        ]);

        $this->postJson("/api/v1/treatment-plans/{$plan->id}/present")
            ->assertUnprocessable();
    }

    public function test_treatment_plan_can_be_rejected(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $plan = TreatmentPlan::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'title'       => 'Test plan',
            'status'      => TreatmentPlan::STATUS_PRESENTED,
            'created_by'  => $this->providerUser->id,
        ]);

        $this->postJson("/api/v1/treatment-plans/{$plan->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', TreatmentPlan::STATUS_REJECTED);
    }

    public function test_receptionist_can_read_treatment_plans(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->getJson('/api/v1/treatment-plans')->assertOk();
    }

    public function test_receptionist_cannot_create_treatment_plan(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson('/api/v1/treatment-plans', [
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'title'       => 'Test',
        ])->assertForbidden();
    }

    // ── Prescriptions ─────────────────────────────────────────────────────────

    public function test_provider_can_create_prescription(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $this->patient->id,
            'items'      => [
                [
                    'drug_name'    => 'Amoxicillin',
                    'dose'         => '500mg',
                    'frequency'    => 'three times daily',
                    'duration'     => '5 days',
                    'quantity'     => '15 capsules',
                    'instructions' => 'Take with food.',
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.provider_id', $this->provider->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.drug_name', 'Amoxicillin');
    }

    public function test_prescription_requires_at_least_one_item(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $this->patient->id,
            'items'      => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_receptionist_cannot_create_prescription(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $this->patient->id,
            'items'      => [['drug_name' => 'Amoxicillin']],
        ])->assertForbidden();
    }

    public function test_prescription_can_be_marked_as_printed(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $prescription = Prescription::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'issued_at'   => now(),
        ]);

        $this->putJson("/api/v1/prescriptions/{$prescription->id}", [
            'is_printed' => true,
        ])->assertOk()
            ->assertJsonPath('data.is_printed', true);

        $this->assertNotNull(
            Prescription::find($prescription->id)->printed_at
        );
    }

    public function test_assistant_can_view_prescriptions(): void
    {
        Sanctum::actingAs($this->assistant, $this->assistant->tokenAbilities());

        $this->getJson('/api/v1/prescriptions')->assertOk();
    }
}
