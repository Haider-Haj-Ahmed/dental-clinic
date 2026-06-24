<?php

namespace Tests\Feature\Api;

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Models\PatientCondition;
use App\Models\PatientMedication;
use App\Models\PerioExam;
use App\Models\PerioMeasure;
use App\Models\Provider;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiPerioRecallTest extends TestCase
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createPerioExamWithMeasures(int $measureCount = 6): PerioExam
    {
        $exam = PerioExam::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'exam_date'   => today(),
        ]);

        $sites = ['MB', 'B', 'DB', 'ML', 'L', 'DL'];
        for ($i = 0; $i < $measureCount; $i++) {
            PerioMeasure::create([
                'perio_exam_id'     => $exam->id,
                'tooth_number'      => 16,
                'site'              => $sites[$i % 6],
                'probing_depth'     => fake()->numberBetween(2, 7),
                'bleeding_on_probe' => fake()->boolean(40),
                'furcation'         => fake()->numberBetween(0, 2),
                'mobility'          => fake()->numberBetween(0, 1),
                'suppuration'       => false,
            ]);
        }

        return $exam;
    }

    private function fakeGeminiPerioResponse(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'risk_level'                       => 'moderate',
                        'risk_score'                       => 55,
                        'contributing_factors'             => [
                            ['factor' => 'Bleeding on probe', 'severity' => 'moderate', 'detail' => '40% of sites bleeding'],
                            ['factor' => 'Deep pockets', 'severity' => 'moderate', 'detail' => 'Multiple sites ≥5mm'],
                        ],
                        'teeth_of_concern'                 => [
                            ['tooth_number' => 16, 'issues' => ['deep pocket', 'furcation involvement'], 'priority' => 'treat'],
                        ],
                        'systemic_risk_factors'            => [],
                        'recommended_recall_interval_months' => 3,
                        'treatment_recommendations'        => ['Full mouth debridement', 'Oral hygiene instruction', 'Review in 3 months'],
                        'prognosis'                        => 'fair',
                        'disclaimer'                       => 'AI-generated risk assessment. Clinical verification required.',
                    ])]]]],
                ],
            ], 200),
        ]);
    }

    private function fakeGeminiRecallResponse(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'prioritised_recalls'       => [
                            ['recall_id' => 1, 'priority_rank' => 1, 'priority_level' => 'urgent', 'reason' => '30 days overdue'],
                            ['recall_id' => 2, 'priority_rank' => 2, 'priority_level' => 'high',   'reason' => '15 days overdue'],
                        ],
                        'summary'                   => ['urgent_count' => 1, 'high_count' => 1, 'medium_count' => 0, 'low_count' => 0],
                        'recommended_contact_order' => [1, 2],
                        'disclaimer'                => 'AI-generated prioritisation for workflow assistance only.',
                    ])]]]],
                ],
            ], 200),
        ]);
    }

    // ── Perio risk scoring — access control ───────────────────────────────────

    public function test_provider_can_score_perio_risk(): void
    {
        $this->fakeGeminiPerioResponse();
        $exam = $this->createPerioExamWithMeasures();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/perio-exams/{$exam->id}/risk-score")
            ->assertOk()
            ->assertJsonPath('data.analysis_type', AiAnalysisResult::TYPE_PERIO_RISK)
            ->assertJsonPath('data.status', AiAnalysisResult::STATUS_PENDING);
    }

    public function test_receptionist_cannot_score_perio_risk(): void
    {
        $exam = $this->createPerioExamWithMeasures();
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson("/api/v1/perio-exams/{$exam->id}/risk-score")
            ->assertForbidden();
    }

    public function test_perio_exam_without_measures_cannot_be_scored(): void
    {
        $exam = PerioExam::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->provider->id,
            'exam_date'   => today(),
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/perio-exams/{$exam->id}/risk-score")
            ->assertUnprocessable();
    }

    public function test_perio_risk_result_has_required_fields(): void
    {
        $this->fakeGeminiPerioResponse();
        $exam = $this->createPerioExamWithMeasures();
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/perio-exams/{$exam->id}/risk-score")
            ->assertOk()
            ->json('data.result');

        $this->assertArrayHasKey('risk_level',                       $result);
        $this->assertArrayHasKey('risk_score',                       $result);
        $this->assertArrayHasKey('contributing_factors',             $result);
        $this->assertArrayHasKey('teeth_of_concern',                 $result);
        $this->assertArrayHasKey('recommended_recall_interval_months', $result);
        $this->assertArrayHasKey('treatment_recommendations',        $result);
        $this->assertArrayHasKey('prognosis',                        $result);
        $this->assertArrayHasKey('disclaimer',                       $result);
    }

    public function test_systemic_conditions_included_in_perio_prompt(): void
    {
        PatientCondition::create([
            'patient_id' => $this->patient->id,
            'condition'  => 'Type 2 Diabetes',
            'status'     => 'active',
        ]);

        $exam = $this->createPerioExamWithMeasures();

        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) use ($exam) {
                $body       = json_decode($request->body(), true);
                $promptText = $body['contents'][0]['parts'][0]['text'] ?? '';

                $this->assertStringContainsString('Diabetes', $promptText);
                $this->assertStringContainsString((string) $exam->measures->count(), $promptText);

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => json_encode([
                            'risk_level'                         => 'high',
                            'risk_score'                         => 70,
                            'contributing_factors'               => [],
                            'teeth_of_concern'                   => [],
                            'systemic_risk_factors'              => ['Uncontrolled diabetes increases perio risk'],
                            'recommended_recall_interval_months' => 3,
                            'treatment_recommendations'          => ['Medical consultation for diabetes management'],
                            'prognosis'                          => 'guarded',
                            'disclaimer'                         => 'AI-generated.',
                        ])]]]],
                    ],
                ], 200);
            },
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $result = $this->postJson("/api/v1/perio-exams/{$exam->id}/risk-score")
            ->assertOk()
            ->json('data.result');

        $this->assertNotEmpty($result['systemic_risk_factors']);
    }

    public function test_duplicate_pending_perio_risk_returned(): void
    {
        $exam = $this->createPerioExamWithMeasures();

        $existing = AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->providerUser->id,
            'source_type'   => 'encounter',
            'source_id'     => $exam->id,
            'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->postJson("/api/v1/perio-exams/{$exam->id}/risk-score")
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertDatabaseCount('ai_analysis_results', 1);
    }

    // ── Patient insights ──────────────────────────────────────────────────────

    public function test_patient_insights_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->providerUser, $this->providerUser->tokenAbilities());

        $this->getJson("/api/v1/patients/{$this->patient->id}/ai-insights")
            ->assertOk()
            ->assertJsonStructure([
                'patient_id',
                'risk_summary' => ['perio_risk_level', 'perio_risk_score'],
                'latest_results',
            ]);
    }

    public function test_patient_insights_shows_null_for_missing_types(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $response = $this->getJson("/api/v1/patients/{$this->patient->id}/ai-insights")
            ->assertOk();

        $this->assertNull($response->json('latest_results.xray_analysis'));
        $this->assertNull($response->json('latest_results.perio_risk'));
        $this->assertNull($response->json('risk_summary.perio_risk_level'));
    }

    public function test_patient_insights_shows_latest_perio_risk(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        AiAnalysisResult::factory()->create([
            'patient_id'    => $this->patient->id,
            'requested_by'  => $this->owner->id,
            'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK,
            'status'        => AiAnalysisResult::STATUS_ACCEPTED,
            'result'        => [
                'risk_level'  => 'high',
                'risk_score'  => 72,
                'disclaimer'  => 'AI-generated.',
            ],
        ]);

        $response = $this->getJson("/api/v1/patients/{$this->patient->id}/ai-insights")
            ->assertOk();

        $this->assertEquals('high', $response->json('risk_summary.perio_risk_level'));
        $this->assertEquals(72,     $response->json('risk_summary.perio_risk_score'));
    }

    public function test_receptionist_cannot_view_patient_insights(): void
    {
        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->getJson("/api/v1/patients/{$this->patient->id}/ai-insights")
            ->assertForbidden();
    }

    // ── Recall prioritisation ─────────────────────────────────────────────────

    public function test_owner_can_prioritise_recalls(): void
    {
        $this->fakeGeminiRecallResponse();

        Recall::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
            'due_date'   => now()->subDays(10)->toDateString(),
            'status'     => Recall::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/v1/recalls/ai-prioritize')
            ->assertOk()
            ->assertJsonPath('data.analysis_type', AiAnalysisResult::TYPE_PERIO_RISK);
    }

    public function test_recall_prioritisation_with_no_pending_recalls_returns_422(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/v1/recalls/ai-prioritize')
            ->assertUnprocessable();
    }

    public function test_prioritisation_result_has_correct_structure(): void
    {
        $this->fakeGeminiRecallResponse();

        Recall::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'due_date'   => now()->subDays(5)->toDateString(),
            'status'     => Recall::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->owner, ['*']);

        $result = $this->postJson('/api/v1/recalls/ai-prioritize')
            ->assertOk()
            ->json('data.result');

        $this->assertArrayHasKey('prioritised_recalls',       $result);
        $this->assertArrayHasKey('summary',                   $result);
        $this->assertArrayHasKey('recommended_contact_order', $result);
        $this->assertArrayHasKey('disclaimer',                $result);
    }

    public function test_receptionist_cannot_prioritise_recalls(): void
    {
        Recall::factory()->create([
            'patient_id' => $this->patient->id,
            'due_date'   => now()->subDay()->toDateString(),
            'status'     => Recall::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->receptionist, $this->receptionist->tokenAbilities());

        $this->postJson('/api/v1/recalls/ai-prioritize')
            ->assertForbidden();
    }

    public function test_recall_prioritisation_accepts_and_stores_result(): void
    {
        $this->fakeGeminiRecallResponse();

        Recall::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'due_date'   => now()->subDays(5)->toDateString(),
            'status'     => Recall::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/v1/recalls/ai-prioritize')->assertOk();

        $this->assertDatabaseHas('ai_analysis_results', [
            'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK,
            'requested_by'  => $this->owner->id,
            'ai_provider'   => 'gemini',
        ]);
    }
}
