<?php

namespace App\Services;

use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\PatientMedicalDocument;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $model;
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->model  = config('ai.models.vision', 'gemini-2.0-flash');
        $this->apiKey = config('ai.api_key', '');
    }

    // ── X-ray / image analysis (Phase 5A) ────────────────────────────────────

    public function analyseImage(
        PatientMedicalDocument $document,
        User $requestedBy,
        string $filePath,
    ): AiAnalysisResult {
        $prompt    = $this->buildXrayPrompt($document);
        $rawResult = $this->callVisionApi($filePath, $document->mime_type, $prompt);

        return AiAnalysisResult::create([
            'patient_id'    => $document->patient_id,
            'requested_by'  => $requestedBy->id,
            'source_type'   => 'document',
            'source_id'     => $document->id,
            'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            'ai_provider'   => 'gemini',
            'ai_model'      => $this->model,
            'input_summary' => "Document #{$document->id} — {$document->document_type} — {$document->title}",
            'result'        => $rawResult,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    // ── SOAP note suggestion (Phase 5B) ───────────────────────────────────────

    public function suggestSoap(
        Encounter $encounter,
        User $requestedBy,
    ): AiAnalysisResult {
        $patient = $encounter->patient->load(['allergies', 'medications', 'conditions']);

        $prompt    = $this->buildSoapPrompt($encounter, $patient);
        $rawResult = $this->callTextApi($prompt);

        return AiAnalysisResult::create([
            'patient_id'    => $encounter->patient_id,
            'requested_by'  => $requestedBy->id,
            'source_type'   => 'encounter',
            'source_id'     => $encounter->id,
            'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            'ai_provider'   => 'gemini',
            'ai_model'      => config('ai.models.text', 'gemini-2.0-flash'),
            'input_summary' => "Encounter #{$encounter->id} — {$encounter->encounter_date->toDateString()}",
            'result'        => $rawResult,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    // ── Gemini Vision API call ────────────────────────────────────────────────

    private function callVisionApi(string $filePath, string $mimeType, string $prompt): array
    {
        $imageData = base64_encode(file_get_contents(storage_path('app/private/'.$filePath)));

        $response = Http::timeout(60)
            ->post("{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $this->systemPrompt()]],
                ],
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data'      => $imageData,
                                ],
                            ],
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.2,
                    'maxOutputTokens' => 1024,
                ],
            ]);

        return $this->handleResponse($response);
    }

    // ── Gemini Text API call ──────────────────────────────────────────────────

    private function callTextApi(string $prompt): array
    {
        $textModel = config('ai.models.text', 'gemini-2.0-flash');

        $response = Http::timeout(60)
            ->post("{$this->baseUrl}/{$textModel}:generateContent?key={$this->apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $this->systemPrompt()]],
                ],
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.3,
                    'maxOutputTokens' => 1500,
                ],
            ]);

        return $this->handleResponse($response);
    }

    private function handleResponse(\Illuminate\Http\Client\Response $response): array
    {
        if ($response->failed()) {
            Log::error('Gemini AI API failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \RuntimeException('AI request failed: '.$response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        return $this->parseStructuredResponse($text);
    }

    // ── Prompts ───────────────────────────────────────────────────────────────

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an AI clinical assistant supporting a licensed dentist. You provide structured observations and suggestions to assist — never replace — the dentist's clinical judgment.

CRITICAL RULES:
- Provide observations and suggestions only — the dentist makes all decisions
- Always include confidence levels and a disclaimer
- Never diagnose — observe and suggest for clinical review
- Return ONLY valid JSON, no markdown fences, no extra text
PROMPT;
    }

    private function buildXrayPrompt(PatientMedicalDocument $document): string
    {
        $type    = $document->document_type;
        $takenAt = $document->taken_at?->toDateString() ?? 'unknown date';

        return <<<PROMPT
Analyse this dental {$type} taken on {$takenAt}.

Return ONLY this JSON structure:
{
  "image_quality": "good|acceptable|poor",
  "image_quality_notes": "string",
  "findings": [
    {
      "tooth_number": "FDI notation or null",
      "region": "string",
      "observation": "string",
      "possible_conditions": ["string"],
      "confidence": "high|medium|low",
      "urgency": "routine|monitor|prompt|urgent"
    }
  ],
  "overall_assessment": "string",
  "recommendations": ["string"],
  "disclaimer": "AI-generated observations for clinical review only. All findings must be verified by the treating dentist."
}
PROMPT;
    }

    private function buildSoapPrompt(Encounter $encounter, $patient): string
    {
        $allergies  = $patient->allergies->pluck('allergen')->implode(', ') ?: 'None documented';
        $medications = $patient->medications
            ->where('end_date', null)
            ->pluck('drug_name')->implode(', ') ?: 'None documented';
        $conditions = $patient->conditions
            ->where('status', 'active')
            ->pluck('condition')->implode(', ') ?: 'None documented';

        $existingSubjective = $encounter->subjective ?? '';
        $existingObjective  = $encounter->objective  ?? '';
        $existingAssessment = $encounter->assessment ?? '';
        $existingPlan       = $encounter->plan       ?? '';

        return <<<PROMPT
A dentist needs help drafting a SOAP note for this clinical encounter.

PATIENT CONTEXT:
- Known allergies: {$allergies}
- Active medications: {$medications}
- Active conditions: {$conditions}

EXISTING NOTES (may be incomplete or just bullet points):
- Subjective: {$existingSubjective}
- Objective: {$existingObjective}
- Assessment: {$existingAssessment}
- Plan: {$existingPlan}

Based on this context, suggest a complete professional SOAP note.

Return ONLY this JSON structure:
{
  "subjective": "expanded professional version of the subjective section",
  "objective": "expanded professional version of the objective section",
  "assessment": "expanded professional version of the assessment/diagnosis section",
  "plan": "expanded professional version of the treatment plan",
  "drug_interaction_flags": [
    {
      "drug": "drug name",
      "flag": "description of potential interaction or concern",
      "severity": "low|moderate|high"
    }
  ],
  "clinical_notes": "any additional observations the dentist should consider",
  "disclaimer": "AI-generated suggestion for clinical review only. The treating dentist must review and modify before finalising."
}
PROMPT;
    }

    // ── Response parser ───────────────────────────────────────────────────────

    private function parseStructuredResponse(string $text): array
    {
        $text = preg_replace('/^```json\s*/m', '', $text);
        $text = preg_replace('/^```\s*/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [
                'parse_error'        => true,
                'raw_response'       => $text,
                'findings'           => [],
                'overall_assessment' => 'Response could not be parsed. See raw_response.',
                'disclaimer'         => 'AI-generated. Requires clinical verification.',
            ];
        }

        return $decoded;
    }
}
