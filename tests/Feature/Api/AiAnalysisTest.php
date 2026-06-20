<?php

namespace Tests\Feature\Api;

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Models\Provider;
use App\Models\User;
use App\Services\AiService;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAnalysisTest extends TestCase
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

        Storage::fake('local');
    }

    // ── Helper — fake AI response ─────────────────────────────────────────────

    private function fakeSuccessfulAiResponse(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode([
                            'image_quality'       => 'good',
                            'image_quality_notes' => 'Clear image',
                            'findings'            => [
                                [
                                    'tooth_number'        => '36',
                                    'region'              => 'lower left first molar',
                                    'observation'         => 'Possible periapical lucency',
                                    'possible_conditions' => ['periapical abscess'],
                                    'confidence'          => 'medium',
                                    'urgency'             => 'prompt',
                                ],
                            ],
                            'overall_assessment' => 'Review tooth 36.',
                            'recommendations'    => ['Examine tooth 36 clinically'],
                            'disclaimer'         => 'AI-generated. Requires clinical verification.',
                        ]),
                    ],
                ],
            ], 200),
        ]);
    }

    private function createImageDocument(): PatientMedicalDocument
    {
        // Put a fake image on the fake disk
        Storage::disk('local')->put('documents/test-xray.jpg', 'fake-image-content');

        return PatientMedicalDocument::factory()->create([
            'patient_id'    => $this->patient->id,
            'uploaded_by'   => $this->providerUser->id,
            'document_type' => 'xray',
            'mime_type'     => 'image/jpeg',
            'file_path'     => 'documents/test-xray.jpg',
            'title'         => 'Periapical X-ray tooth 36',
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_provider_can_trigger_analysis(): void
    {
        $this->fakeSuccessfulAiResponse();
        $document = $this->createImageDocument();

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/analyze")
            ->assertOk()
            ->assertJsonPath('data.analysis_type', AiAnalysisResult::TYPE_XRAY_ANALYSIS)
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_PENDING);
    }

    public function test_receptionist_cannot_trigger_analysis(): void
    {
        $document = $this->createImageDocument();
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/analyze")
            ->assertForbidden();
    }

    public function test_owner_can_trigger_analysis(): void
    {
        $this->fakeSuccessfulAiResponse();
        $document = $this->createImageDocument();

        Sanctum::actingAs($this->owner, $this->owner->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/analyze")
            ->assertOk();
    }

    // ── Analysis trigger ──────────────────────────────────────────────────────

    public function test_analysis_stores_result_with_correct_fields(): void
    {
        $this->fakeSuccessfulAiResponse();
        $document = $this->createImageDocument();

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $response = $this->postJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/analyze")
            ->assertOk();

        $this->assertDatabaseHas('ai_analysis_results', [
            'patient_id'    => $this->patient->id,
            'source_type'   => 'document',
            'source_id'     => $document->id,
            'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            'ai_provider'   => 'anthropic',
            'status'        => AiAnalysisResult::STATUS_PENDING,
            'requested_by'  => $this->providerUser->id,
        ]);

        // Result should contain structured findings
        $result = $response->json('data.result');
        $this->assertArrayHasKey('findings', $result);
        $this->assertArrayHasKey('overall_assessment', $result);
        $this->assertArrayHasKey('disclaimer', $result);
    }

    public function test_non_image_document_cannot_be_analysed(): void
    {
        $document = PatientMedicalDocument::factory()->create([
            'patient_id'    => $this->patient->id,
            'uploaded_by'   => $this->providerUser->id,
            'mime_type'     => 'application/pdf',
            'file_path'     => 'documents/consent.pdf',
            'document_type' => 'consent_form',
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/analyze")
            ->assertUnprocessable();
    }

    public function test_document_belonging_to_different_patient_returns_404(): void
    {
        $this->fakeSuccessfulAiResponse();
        $otherPatient = Patient::factory()->create();
        $document = $this->createImageDocument();

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$otherPatient->id}/documents/{$document->id}/analyze")
            ->assertNotFound();
    }

    public function test_existing_pending_analysis_returned_instead_of_creating_duplicate(): void
    {
        $document = $this->createImageDocument();

        $existing = AiAnalysisResult::factory()->create([
            'patient_id'  => $this->patient->id,
            'requested_by'=> $this->providerUser->id,
            'source_type' => 'document',
            'source_id'   => $document->id,
            'status'      => AiAnalysisResult::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/analyze")
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertDatabaseCount('ai_analysis_results', 1);
    }

    // ── Listing results ───────────────────────────────────────────────────────

    public function test_provider_can_list_patient_ai_results(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        AiAnalysisResult::factory()->count(3)->create([
            'patient_id'   => $this->patient->id,
            'requested_by' => $this->providerUser->id,
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/ai-results")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_results_can_be_filtered_by_status(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        AiAnalysisResult::factory()->create(['patient_id' => $this->patient->id, 'requested_by' => $this->owner->id, 'status' => AiAnalysisResult::STATUS_PENDING]);
        AiAnalysisResult::factory()->accepted()->create(['patient_id' => $this->patient->id, 'requested_by' => $this->owner->id]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/ai-results?status=pending")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', AiAnalysisResult::STATUS_PENDING);
    }

    // ── Review — accept ───────────────────────────────────────────────────────

    public function test_provider_can_accept_their_own_result(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'   => $this->patient->id,
            'requested_by' => $this->providerUser->id,
            'status'       => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/accept", [
            'reviewer_notes' => 'Confirmed periapical lesion on examination.',
        ])->assertOk()
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_ACCEPTED)
            ->assertJsonPath('data.reviewer_notes', 'Confirmed periapical lesion on examination.');

        $this->assertDatabaseHas('ai_analysis_results', [
            'id'          => $result->id,
            'status'      => AiAnalysisResult::STATUS_ACCEPTED,
            'reviewed_by' => $this->providerUser->id,
        ]);
    }

    public function test_provider_cannot_accept_another_providers_result(): void
    {
        $otherProvider = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($otherProvider, $otherProvider->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'   => $this->patient->id,
            'requested_by' => $this->providerUser->id, // different provider
            'status'       => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/accept")
            ->assertForbidden();
    }

    public function test_already_accepted_result_cannot_be_accepted_again(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->accepted()->create([
            'patient_id'   => $this->patient->id,
            'requested_by' => $this->providerUser->id,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/accept")
            ->assertUnprocessable();
    }

    // ── Review — dismiss ──────────────────────────────────────────────────────

    public function test_provider_can_dismiss_result(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = AiAnalysisResult::factory()->create([
            'patient_id'   => $this->patient->id,
            'requested_by' => $this->providerUser->id,
            'status'       => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/dismiss", [
            'reviewer_notes' => 'No clinical correlation found.',
        ])->assertOk()
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_DISMISSED);
    }

    public function test_owner_can_dismiss_any_result(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $result = AiAnalysisResult::factory()->create([
            'patient_id'   => $this->patient->id,
            'requested_by' => $this->providerUser->id,
            'status'       => AiAnalysisResult::STATUS_PENDING,
        ]);

        $this->postJson("/api/v1/ai-results/{$result->id}/dismiss")
            ->assertOk()
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_DISMISSED);
    }
}
