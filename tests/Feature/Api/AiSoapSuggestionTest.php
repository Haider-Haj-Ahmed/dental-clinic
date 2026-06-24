<?php

namespace Tests\Feature\Api;

use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedication;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiSoapSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $providerUser;
    private Provider $provider;
    private User $receptionist;
    private Patient $patient;
    private Encounter $encounter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->provider     = Provider::factory()->create(['user_id' => $this->providerUser->id]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->patient      = Patient::factory()->create();

        $this->encounter = Encounter::create([
            'patient_id'     => $this->patient->id,
            'provider_id'    => $this->provider->id,
            'encounter_date' => today(),
            'subjective'     => 'Patient reports toothache upper right',
            'objective'      => 'Tooth 16 tender to percussion',
            'is_locked'      => false,
        ]);
    }

    private function fakeGeminiTextResponse(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'subjective'            => 'Patient presents with spontaneous throbbing pain in the upper right quadrant, worsening with hot stimuli and at night. Pain rated 7/10.',
                                        'objective'             => 'Tooth 16 is tender to percussion (+++) and palpation (+). Cold test: prolonged response. Periapical radiograph shows widened PDL space.',
                                        'assessment'            => 'Symptomatic irreversible pulpitis tooth 16 with symptomatic apical periodontitis.',
                                        'plan'                  => 'Root canal therapy tooth 16. Prescribe Amoxicillin 500mg TDS x 5 days if signs of infection. Review in 1 week.',
                                        'drug_interaction_flags'=> [],
                                        'clinical_notes'        => 'Confirm no allergy to penicillin before prescribing Amoxicillin.',
                                        'disclaimer'            => 'AI-generated suggestion for clinical review only. The treating dentist must review and modify before finalising.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_provider_can_request_soap_suggestion(): void
    {
        $this->fakeGeminiTextResponse();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertOk()
            ->assertJsonPath('data.analysis_type', AiAnalysisResult::TYPE_SOAP_SUGGESTION)
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_PENDING);
    }

    public function test_receptionist_cannot_request_soap_suggestion(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertForbidden();
    }

    public function test_owner_can_request_soap_suggestion(): void
    {
        $this->fakeGeminiTextResponse();
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertOk();
    }

    // ── Suggestion content ────────────────────────────────────────────────────

    public function test_suggestion_stores_correct_fields(): void
    {
        $this->fakeGeminiTextResponse();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertOk();

        $this->assertDatabaseHas('ai_analysis_results', [
            'patient_id'    => $this->patient->id,
            'source_type'   => 'encounter',
            'source_id'     => $this->encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'ai_provider'   => 'gemini',
            'requested_by'  => $this->providerUser->id,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    public function test_suggestion_result_contains_soap_fields(): void
    {
        $this->fakeGeminiTextResponse();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertOk()
            ->json('data.result');

        $this->assertArrayHasKey('subjective',             $result);
        $this->assertArrayHasKey('objective',              $result);
        $this->assertArrayHasKey('assessment',             $result);
        $this->assertArrayHasKey('plan',                   $result);
        $this->assertArrayHasKey('drug_interaction_flags', $result);
        $this->assertArrayHasKey('disclaimer',             $result);
    }

    public function test_patient_allergies_and_medications_included_in_context(): void
    {
        // These should be included in the prompt sent to Gemini — Http::fake captures the request
        PatientAllergy::create([
            'patient_id' => $this->patient->id,
            'allergen'   => 'Penicillin',
            'severity'   => 'severe',
        ]);

        PatientMedication::create([
            'patient_id' => $this->patient->id,
            'drug_name'  => 'Metformin',
            'dose'       => '500mg',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) {
                // Verify the prompt contains patient context
                $body = json_decode($request->body(), true);
                $promptText = $body['contents'][0]['parts'][0]['text'] ?? '';

                $this->assertStringContainsString('Penicillin', $promptText);
                $this->assertStringContainsString('Metformin',  $promptText);

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => json_encode([
                            'subjective'            => 'Test subjective',
                            'objective'             => 'Test objective',
                            'assessment'            => 'Test assessment',
                            'plan'                  => 'Test plan',
                            'drug_interaction_flags'=> [
                                ['drug' => 'Amoxicillin', 'flag' => 'Patient allergic to Penicillin — do not prescribe', 'severity' => 'high'],
                            ],
                            'clinical_notes' => 'Avoid penicillin-class antibiotics.',
                            'disclaimer'     => 'AI-generated.',
                        ])]]]],
                    ],
                ], 200);
            },
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertOk()
            ->json('data.result');

        // Drug interaction flag should be present for penicillin allergy
        $this->assertNotEmpty($result['drug_interaction_flags']);
        $this->assertEquals('high', $result['drug_interaction_flags'][0]['severity']);
    }

    public function test_locked_encounter_cannot_get_soap_suggestion(): void
    {
        $this->encounter->update(['is_locked' => true]);
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertUnprocessable();
    }

    public function test_existing_pending_suggestion_returned_without_new_api_call(): void
    {
        $existing = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'encounter',
            'source_id'     => $this->encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        // No Http::fake — if a real call was made it would fail
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/encounters/{$this->encounter->id}/suggest-soap")
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertDatabaseCount('ai_analysis_results', 1);
    }

    // ── Apply SOAP to encounter ───────────────────────────────────────────────

    public function test_provider_can_apply_soap_suggestion_to_encounter(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'encounter',
            'source_id'     => $this->encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
            'result'        => [
                'subjective'            => 'AI suggested subjective',
                'objective'             => 'AI suggested objective',
                'assessment'            => 'AI suggested assessment',
                'plan'                  => 'AI suggested plan',
                'drug_interaction_flags'=> [],
                'disclaimer'            => 'AI-generated.',
            ],
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/apply-soap", [
            'reviewer_notes' => 'Applied with minor edits.',
        ])->assertOk()
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_ACCEPTED);

        $this->assertDatabaseHas('encounters', [
            'id'         => $this->encounter->id,
            'subjective' => 'AI suggested subjective',
            'assessment' => 'AI suggested assessment',
        ]);
    }

    public function test_provider_can_override_fields_when_applying(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'encounter',
            'source_id'     => $this->encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
            'result'        => [
                'subjective' => 'AI version',
                'objective'  => 'AI version',
                'assessment' => 'AI version',
                'plan'       => 'AI version',
                'disclaimer' => 'AI-generated.',
            ],
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/apply-soap", [
            'subjective' => 'Doctor edited subjective',
            'plan'       => 'Doctor edited plan',
        ])->assertOk();

        $this->assertDatabaseHas('encounters', [
            'id'         => $this->encounter->id,
            'subjective' => 'Doctor edited subjective',
            'objective'  => 'AI version',  // not overridden — AI value used
            'plan'       => 'Doctor edited plan',
        ]);
    }

    public function test_apply_soap_only_works_on_soap_suggestion_type(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        // Create an xray_analysis result — apply-soap should reject it
        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'document',
            'source_id'     => 1,
            'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/apply-soap")
            ->assertUnprocessable();
    }

    public function test_apply_soap_cannot_write_to_locked_encounter(): void
    {
        $this->encounter->update(['is_locked' => true]);
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'encounter',
            'source_id'     => $this->encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/apply-soap")
            ->assertUnprocessable();
    }

    public function test_provider_cannot_apply_another_providers_suggestion(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($other, $other->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'encounter',
            'source_id'     => $this->encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/apply-soap")
            ->assertForbidden();
    }
}
