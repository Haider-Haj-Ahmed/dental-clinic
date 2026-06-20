<?php

namespace Database\Factories;

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiAnalysisResult> */
class AiAnalysisResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'    => Patient::factory(),
            'requested_by'  => User::factory()->state(['role' => User::ROLE_PROVIDER]),
            'source_type'   => 'document',
            'source_id'     => PatientMedicalDocument::factory(),
            'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            'ai_provider'   => 'anthropic',
            'ai_model'      => 'claude-haiku-4-5-20251001',
            'input_summary' => 'Document #1 — xray — Periapical X-ray',
            'result'        => [
                'image_quality'      => 'good',
                'image_quality_notes'=> 'Clear image, all teeth visible',
                'findings'           => [
                    [
                        'tooth_number'       => '36',
                        'region'             => 'lower left first molar',
                        'observation'        => 'Possible periapical lucency',
                        'possible_conditions'=> ['periapical abscess', 'granuloma'],
                        'confidence'         => 'medium',
                        'urgency'            => 'prompt',
                    ],
                ],
                'overall_assessment' => 'Review tooth 36 for possible periapical pathology.',
                'recommendations'    => ['Clinical examination of tooth 36', 'Consider CBCT if periapical pathology confirmed'],
                'disclaimer'         => 'AI-generated observations require clinical verification.',
            ],
            'status'        => AiAnalysisResult::STATUS_PENDING,
            'reviewed_by'   => null,
            'reviewed_at'   => null,
            'reviewer_notes'=> null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status'         => AiAnalysisResult::STATUS_ACCEPTED,
            'reviewed_by'    => User::factory()->state(['role' => User::ROLE_PROVIDER]),
            'reviewed_at'    => now(),
            'reviewer_notes' => 'Clinically confirmed. Will schedule RCT.',
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn () => [
            'status'         => AiAnalysisResult::STATUS_DISMISSED,
            'reviewed_by'    => User::factory()->state(['role' => User::ROLE_PROVIDER]),
            'reviewed_at'    => now(),
            'reviewer_notes' => 'No clinical signs of pathology on examination.',
        ]);
    }
}
