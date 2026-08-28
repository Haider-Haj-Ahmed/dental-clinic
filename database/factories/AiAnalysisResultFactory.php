<?php

namespace Database\Factories;

use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiAnalysisResultFactory extends Factory
{
    protected $model = AiAnalysisResult::class;

    public function definition(): array
    {
        // Default to a SOAP suggestion sourced from an encounter
        return [
            'patient_id'    => Patient::factory(),
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'source_type'   => 'encounter',
            'source_id'     => function (array $attributes) {
                // Encounter patient must match result patient
                return Encounter::factory()
                    ->forPatient(Patient::find($attributes['patient_id']))
                    ->create()
                    ->id;
            },
            'status'        => AiAnalysisResult::STATUS_PENDING,
            'requested_by'  => User::factory(),
            'reviewed_by'   => null,
            'reviewed_at'   => null,
            'reviewer_notes'=> null,
            'ai_provider'   => 'google',
            'ai_model'      => 'gemini-2.0-flash',
            'input_summary' => $this->faker->sentence(),
            'result'        => [
                'soap' => [
                    'S' => $this->faker->paragraph(),
                    'O' => $this->faker->paragraph(),
                    'A' => $this->faker->sentence(),
                    'P' => $this->faker->paragraph(),
                ],
                'drug_interactions' => [],
                'clinical_nuances'  => $this->faker->optional(.5)->sentence(),
                'tags'              => [],
            ],
        ];
    }

    // ── Type states ────────────────────────────────────────────

    public function soap(): static
    {
        return $this->state(['analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION]);
    }

    public function xray(): static
    {
        return $this->state(function (array $attributes) {
            $patient = Patient::find($attributes['patient_id']);

            // Document patient must match result patient
            $doc = PatientMedicalDocument::factory()
                ->forPatient($patient)
                ->create();

            return [
                'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
                'source_type'   => 'document',
                'source_id'     => $doc->id,
                'result'        => [
                    'findings'  => $this->faker->paragraph(),
                    'urgency'   => $this->faker->randomElement(['low', 'medium', 'high']),
                    'fdi_teeth' => [$this->faker->numberBetween(11, 48)],
                    'summary'   => $this->faker->sentence(),
                ],
            ];
        });
    }

    public function prescription(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
                'result'        => [
                    'items' => [
                        [
                            'drug_name'    => 'Metronidazole 500mg',
                            'dose'         => '500mg',
                            'frequency'    => 'three times daily',
                            'duration'     => '5 days',
                            'instructions' => 'Take with food.',
                        ],
                    ],
                    'drug_interactions' => [],
                    'clinical_nuances'  => $this->faker->optional(.4)->sentence(),
                    'tags'              => [],
                ],
            ];
        });
    }

    public function perio(): static
    {
        return $this->state(function (array $attributes) {
            $score = $this->faker->numberBetween(1, 10);
            $level = match(true) {
                $score <= 3  => 'low',
                $score <= 6  => 'moderate',
                default      => 'high',
            };
            return [
                'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK,
                'result'        => [
                    'risk_score'      => $score,
                    'risk_level'      => $level,
                    'findings'        => $this->faker->paragraph(),
                    'recommendations' => $this->faker->sentence(),
                    'tags'            => [$level . '-risk'],
                ],
            ];
        });
    }

    // ── Status states ──────────────────────────────────────────

    public function pending(): static
    {
        return $this->state([
            'status'      => AiAnalysisResult::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status'      => AiAnalysisResult::STATUS_ACCEPTED,
                'reviewed_by' => User::factory(),
                'reviewed_at' => now()->subMinutes($this->faker->numberBetween(5, 1440)),
            ];
        });
    }

    public function dismissed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status'         => AiAnalysisResult::STATUS_DISMISSED,
                'reviewed_by'    => User::factory(),
                'reviewed_at'    => now()->subMinutes($this->faker->numberBetween(5, 1440)),
                'reviewer_notes' => $this->faker->optional(.6)->sentence(),
            ];
        });
    }

    // ── Binding helpers ────────────────────────────────────────

    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }

    public function requestedBy(User $user): static
    {
        return $this->state(['requested_by' => $user->id]);
    }
}
