<?php

namespace Tests\Feature\Api;

use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientMedication;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiPrescriptionSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $providerUser;
    private Provider $provider;
    private User $receptionist;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->provider     = Provider::factory()->create(['user_id' => $this->providerUser->id]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->patient      = Patient::factory()->create();
    }

    // ── Gemini fake response ──────────────────────────────────────────────────

    private function fakeGeminiPrescriptionResponse(array $override = []): void
    {
        $default = [
            'suggested_items' => [
                [
                    'drug_name'    => 'Amoxicillin',
                    'dose'         => '500mg',
                    'frequency'    => 'three times daily',
                    'duration'     => '5 days',
                    'quantity'     => '15 capsules',
                    'instructions' => 'Take with food. Complete the full course.',
                    'indication'   => 'Dental infection / periapical abscess prophylaxis',
                    'confidence'   => 'high',
                ],
                [
                    'drug_name'    => 'Ibuprofen',
                    'dose'         => '400mg',
                    'frequency'    => 'every 6 hours as needed',
                    'duration'     => '3 days',
                    'quantity'     => '12 tablets',
                    'instructions' => 'Take with food. Do not exceed 1200mg per day.',
                    'indication'   => 'Pain and inflammation management',
                    'confidence'   => 'high',
                ],
            ],
            'interaction_warnings' => [],
            'contraindications'    => [],
            'general_notes'        => 'Patient should be reviewed in 5 days.',
            'disclaimer'           => 'AI-generated prescription suggestions for clinical review only.',
        ];

        $result = array_merge($default, $override);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode($result)]]],
                ]],
            ], 200),
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_provider_can_request_prescription_suggestion(): void
    {
        $this->fakeGeminiPrescriptionResponse();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Periapical abscess tooth 46 with acute infection',
        ])->assertOk()
            ->assertJsonPath('data.analysis_type', AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION)
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_PENDING);
    }

    public function test_receptionist_cannot_request_prescription_suggestion(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Periapical abscess',
        ])->assertForbidden();
    }

    public function test_owner_can_request_prescription_suggestion(): void
    {
        $this->fakeGeminiPrescriptionResponse();
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Periapical abscess tooth 46 with acute infection',
        ])->assertOk();
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_diagnosis_is_required(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('diagnosis');
    }

    public function test_diagnosis_must_be_at_least_10_characters(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Too short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('diagnosis');
    }

    // ── Result content ────────────────────────────────────────────────────────

    public function test_suggestion_stores_correct_fields(): void
    {
        $this->fakeGeminiPrescriptionResponse();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Periapical abscess tooth 46 with acute infection',
        ])->assertOk();

        $this->assertDatabaseHas('ai_analysis_results', [
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'ai_provider'   => 'gemini',
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    public function test_result_contains_required_prescription_fields(): void
    {
        $this->fakeGeminiPrescriptionResponse();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Periapical abscess tooth 46 with acute infection',
        ])->assertOk()->json('data.result');

        $this->assertArrayHasKey('suggested_items',      $result);
        $this->assertArrayHasKey('interaction_warnings', $result);
        $this->assertArrayHasKey('contraindications',    $result);
        $this->assertArrayHasKey('disclaimer',           $result);
        $this->assertNotEmpty($result['suggested_items']);
    }

    public function test_allergy_generates_contraindication_in_prompt(): void
    {
        PatientAllergy::create([
            'patient_id' => $this->patient->id,
            'allergen'   => 'Penicillin',
            'severity'   => 'severe',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) {
                $body       = json_decode($request->body(), true);
                $promptText = $body['contents'][0]['parts'][0]['text'] ?? '';

                // Verify allergy is in the prompt
                $this->assertStringContainsString('Penicillin', $promptText);

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => json_encode([
                            'suggested_items'     => [[
                                'drug_name'    => 'Metronidazole',
                                'dose'         => '400mg',
                                'frequency'    => 'twice daily',
                                'duration'     => '5 days',
                                'quantity'     => '10 tablets',
                                'instructions' => 'Take with food',
                                'indication'   => 'Avoid penicillin due to allergy — use metronidazole for anaerobic coverage',
                                'confidence'   => 'high',
                            ]],
                            'interaction_warnings' => [],
                            'contraindications'    => [[
                                'drug'     => 'Amoxicillin',
                                'reason'   => 'Patient has documented penicillin allergy',
                                'severity' => 'high',
                            ]],
                            'general_notes' => 'Avoid all penicillin-class antibiotics.',
                            'disclaimer'    => 'AI-generated.',
                        ])]]]],
                    ],
                ], 200);
            },
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Periapical abscess — patient has penicillin allergy',
        ])->assertOk()->json('data.result');

        $this->assertNotEmpty($result['contraindications']);
        $this->assertEquals('high', $result['contraindications'][0]['severity']);
    }

    public function test_current_medications_included_in_prompt(): void
    {
        PatientMedication::create([
            'patient_id' => $this->patient->id,
            'drug_name'  => 'Warfarin',
            'dose'       => '5mg',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) {
                $body       = json_decode($request->body(), true);
                $promptText = $body['contents'][0]['parts'][0]['text'] ?? '';

                $this->assertStringContainsString('Warfarin', $promptText);

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => json_encode([
                            'suggested_items'     => [],
                            'interaction_warnings'=> [[
                                'drug'          => 'Ibuprofen',
                                'interacts_with'=> 'Warfarin',
                                'warning'       => 'NSAIDs increase bleeding risk in patients on anticoagulants',
                                'severity'      => 'high',
                            ]],
                            'contraindications'   => [],
                            'general_notes'       => 'Consider paracetamol instead of NSAIDs.',
                            'disclaimer'          => 'AI-generated.',
                        ])]]]],
                    ],
                ], 200);
            },
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => 'Post-extraction pain management for patient on anticoagulation',
        ])->assertOk()->json('data.result');

        $this->assertNotEmpty($result['interaction_warnings']);
        $this->assertEquals('high', $result['interaction_warnings'][0]['severity']);
    }

    public function test_pending_duplicate_returned_without_api_call(): void
    {
        $diagnosis = 'Periapical abscess tooth 46 with acute infection';

        $existing = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
            'input_summary' => "Patient #{$this->patient->id} — diagnosis: {$diagnosis}",
        ]);

        // No Http::fake — real call would fail
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/prescription-suggestions", [
            'diagnosis' => $diagnosis,
        ])->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertDatabaseCount('ai_analysis_results', 1);
    }

    // ── Create prescription from suggestion ───────────────────────────────────

    public function test_provider_can_create_prescription_from_suggestion(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/create-prescription", [
            'notes'          => 'Issued post-extraction.',
            'reviewer_notes' => 'Confirmed appropriate. Issued as suggested.',
            'items'          => [
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
            ->assertJsonStructure(['message', 'prescription' => ['id', 'issued_at', 'items_count'], 'ai_result']);

        $this->assertDatabaseHas('prescriptions', [
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
        ]);

        $this->assertDatabaseHas('prescription_items', [
            'drug_name' => 'Amoxicillin',
            'dose'      => '500mg',
        ]);

        $this->assertDatabaseHas('ai_analysis_results', [
            'id'          => $result->id,
            'status'      => AiAnalysisResult::STATUS_ACCEPTED,
            'reviewed_by' => $this->providerUser->id,
        ]);
    }

    public function test_create_prescription_requires_at_least_one_item(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/create-prescription", [
            'items' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_create_prescription_fails_without_provider_profile(): void
    {
        // User with no provider profile
        $userNoProfile = User::factory()->create(['role' => User::ROLE_OWNER]);
        Sanctum::actingAs($userNoProfile, ['*']);

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/create-prescription", [
            'items' => [['drug_name' => 'Amoxicillin', 'dose' => '500mg']],
        ])->assertUnprocessable();
    }

    public function test_create_prescription_rejects_non_prescription_suggestion_type(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/create-prescription", [
            'items' => [['drug_name' => 'Amoxicillin']],
        ])->assertUnprocessable();
    }

    public function test_provider_cannot_create_prescription_from_another_providers_result(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($other, $other->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/create-prescription", [
            'items' => [['drug_name' => 'Amoxicillin', 'dose' => '500mg']],
        ])->assertForbidden();
    }

    public function test_already_accepted_result_cannot_create_prescription(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->accepted()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/create-prescription", [
            'items' => [['drug_name' => 'Amoxicillin', 'dose' => '500mg']],
        ])->assertUnprocessable();
    }
}
