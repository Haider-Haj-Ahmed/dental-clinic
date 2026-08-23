<?php

namespace App\Services;

use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Models\PerioExam;
use App\Models\Recall;
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

    // ── Prescription suggestion (Phase 5C) ──────────────────────────────────

    public function suggestPrescription(
        Patient $patient,
        string $diagnosis,
        User $requestedBy,
        ?int $encounterId = null,
    ): AiAnalysisResult {
        $patient->loadMissing(['allergies', 'medications', 'conditions']);

        $prompt    = $this->buildPrescriptionPrompt($patient, $diagnosis);
        $rawResult = $this->callTextApi($prompt);

        return AiAnalysisResult::create([
            'patient_id'    => $patient->id,
            'requested_by'  => $requestedBy->id,
            'source_type'   => $encounterId ? 'encounter' : 'patient',
            'source_id'     => $encounterId ?? $patient->id,
            'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            'ai_provider'   => 'gemini',
            'ai_model'      => config('ai.models.text', 'gemini-2.0-flash'),
            'input_summary' => "Patient #{$patient->id} — diagnosis: {$diagnosis}",
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
                    'temperature'      => 0.2,
                    'maxOutputTokens'  => 8192,
                    'responseMimeType' => 'application/json',
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
                    'temperature'      => 0.3,
                    'maxOutputTokens'  => 8192,
                    'responseMimeType' => 'application/json',
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

        $text = $this->extractResponseText($response);

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

    private function buildPrescriptionPrompt(Patient $patient, string $diagnosis): string
    {
        $allergies = $patient->allergies->pluck('allergen')->implode(', ') ?: 'None documented';

        $currentMeds = $patient->medications
            ->whereNull('end_date')
            ->map(fn ($m) => "{$m->drug_name} {$m->dose}")
            ->implode(', ') ?: 'None documented';

        $conditions = $patient->conditions
            ->where('status', 'active')
            ->pluck('condition')->implode(', ') ?: 'None documented';

        return <<<PROMPT
A dentist needs prescription suggestions for the following dental case.

PATIENT SAFETY INFORMATION:
- Known drug allergies: {$allergies}
- Current medications: {$currentMeds}
- Active medical conditions: {$conditions}

DIAGNOSIS / CLINICAL SITUATION:
{$diagnosis}

Suggest appropriate dental prescriptions. Check for interactions with current medications and contraindications based on allergies and conditions.

Return ONLY this JSON structure:
{
  "suggested_items": [
    {
      "drug_name": "string",
      "dose": "string (e.g. 500mg)",
      "frequency": "string (e.g. three times daily)",
      "duration": "string (e.g. 5 days)",
      "quantity": "string (e.g. 15 tablets)",
      "instructions": "string (e.g. Take with food)",
      "indication": "string (why this drug is suggested)",
      "confidence": "high|medium|low"
    }
  ],
  "interaction_warnings": [
    {
      "drug": "string",
      "interacts_with": "string",
      "warning": "string",
      "severity": "low|moderate|high"
    }
  ],
  "contraindications": [
    {
      "drug": "string",
      "reason": "string",
      "severity": "low|moderate|high"
    }
  ],
  "general_notes": "any additional clinical notes for the prescribing dentist",
  "disclaimer": "AI-generated prescription suggestions for clinical review only. The prescribing dentist is solely responsible for all prescriptions issued. Always verify drug interactions and patient allergies before prescribing."
}
PROMPT;
    }

    // ── Perio risk scoring (Phase 5D) ────────────────────────────────────────

    public function scorePerioRisk(
        PerioExam $exam,
        User $requestedBy,
    ): AiAnalysisResult {
        $exam->loadMissing('measures');
        $patient = $exam->patient->loadMissing(['conditions', 'medications']);

        $prompt    = $this->buildPerioRiskPrompt($exam, $patient);
        $rawResult = $this->callTextApi($prompt);

        return AiAnalysisResult::create([
            'patient_id'    => $exam->patient_id,
            'requested_by'  => $requestedBy->id,
            'source_type'   => 'encounter',
            'source_id'     => $exam->id,
            'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK,
            'ai_provider'   => 'gemini',
            'ai_model'      => config('ai.models.text', 'gemini-2.0-flash'),
            'input_summary' => "PerioExam #{$exam->id} — {$exam->exam_date->toDateString()} — {$exam->measures->count()} sites",
            'result'        => $rawResult,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    // ── Recall prioritisation (Phase 5D) ─────────────────────────────────────

    public function prioritiseRecalls(
        \Illuminate\Support\Collection $recalls,
        User $requestedBy,
    ): AiAnalysisResult {
        $prompt    = $this->buildRecallPriorityPrompt($recalls);
        $rawResult = $this->callTextApi($prompt);

        // Linked to no single patient — use the requesting user as anchor
        return AiAnalysisResult::create([
            'patient_id'    => $recalls->first()->patient_id,
            'requested_by'  => $requestedBy->id,
            'source_type'   => 'patient',
            'source_id'     => $recalls->first()->patient_id,
            'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK, // reuse type slot — dedicated type not needed
            'ai_provider'   => 'gemini',
            'ai_model'      => config('ai.models.text', 'gemini-2.0-flash'),
            'input_summary' => "Recall prioritisation — {$recalls->count()} pending recalls",
            'result'        => $rawResult,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    // ── Perio risk prompt ─────────────────────────────────────────────────────

    private function buildPerioRiskPrompt(PerioExam $exam, $patient): string
    {
        $conditions = $patient->conditions
            ->where('status', 'active')
            ->pluck('condition')->implode(', ') ?: 'None documented';

        $medications = $patient->medications
            ->whereNull('end_date')
            ->pluck('drug_name')->implode(', ') ?: 'None documented';

        // Summarise measurements for the prompt
        $totalSites      = $exam->measures->count();
        $bleedingSites   = $exam->measures->where('bleeding_on_probe', true)->count();
        $suppSites       = $exam->measures->where('suppuration', true)->count();
        $deepPockets     = $exam->measures->where('probing_depth', '>=', 5)->count();
        $furcationInv    = $exam->measures->where('furcation', '>=', 2)->count();
        $mobilityIssues  = $exam->measures->where('mobility', '>=', 2)->count();
        $maxDepth        = $exam->measures->max('probing_depth') ?? 0;
        $avgDepth        = round($exam->measures->avg('probing_depth') ?? 0, 1);

        // Per-tooth summary for deeper analysis
        $toothSummary = $exam->measures
            ->groupBy('tooth_number')
            ->map(fn ($sites) => [
                'max_depth'      => $sites->max('probing_depth'),
                'bleeding_sites' => $sites->where('bleeding_on_probe', true)->count(),
                'furcation'      => $sites->max('furcation'),
                'mobility'       => $sites->max('mobility'),
            ])
            ->toArray();

        $toothSummaryJson = json_encode($toothSummary, JSON_PRETTY_PRINT);

        return <<<PROMPT
Analyse this periodontal examination and provide a risk assessment.

PATIENT MEDICAL CONTEXT:
- Active conditions: {$conditions}
- Current medications: {$medications}

EXAMINATION SUMMARY:
- Exam date: {$exam->exam_date->toDateString()}
- Total sites recorded: {$totalSites}
- Sites with bleeding on probe: {$bleedingSites}
- Sites with suppuration: {$suppSites}
- Sites with probing depth ≥ 5mm: {$deepPockets}
- Sites with furcation involvement ≥ class II: {$furcationInv}
- Sites with mobility ≥ grade II: {$mobilityIssues}
- Maximum probing depth: {$maxDepth}mm
- Average probing depth: {$avgDepth}mm

PER-TOOTH DATA (tooth number → max_depth, bleeding_sites, furcation, mobility):
{$toothSummaryJson}

Return ONLY this JSON structure:
{
  "risk_level": "low|moderate|high|severe",
  "risk_score": 0-100,
  "contributing_factors": [
    {
      "factor": "string",
      "severity": "low|moderate|high",
      "detail": "string"
    }
  ],
  "teeth_of_concern": [
    {
      "tooth_number": number,
      "issues": ["string"],
      "priority": "monitor|treat|urgent"
    }
  ],
  "systemic_risk_factors": ["string"],
  "recommended_recall_interval_months": number,
  "treatment_recommendations": ["string"],
  "prognosis": "good|fair|poor|guarded",
  "disclaimer": "AI-generated periodontal risk assessment for clinical review only. The treating dentist must verify all findings clinically."
}
PROMPT;
    }

    // ── Recall priority prompt ────────────────────────────────────────────────

    private function buildRecallPriorityPrompt(\Illuminate\Support\Collection $recalls): string
    {
        $recallData = $recalls->map(fn ($r) => [
            'recall_id'   => $r->id,
            'patient_id'  => $r->patient_id,
            'patient_name'=> trim(($r->patient->first_name ?? '') . ' ' . ($r->patient->last_name ?? '')),
            'due_date'    => $r->due_date?->toDateString(),
            'days_overdue'=> $r->due_date ? max(0, now()->diffInDays($r->due_date, false) * -1) : 0,
            'status'      => $r->status,
            'notes'       => $r->notes,
        ])->toArray();

        $recallJson = json_encode($recallData, JSON_PRETTY_PRINT);
        $count      = $recalls->count();

        return <<<PROMPT
A dental clinic has {$count} pending recalls that need to be prioritised for outreach.

PENDING RECALLS:
{$recallJson}

Prioritise these recalls for the receptionist to contact patients. Consider:
- Days overdue (higher = more urgent)
- Patient notes suggesting clinical urgency
- Balanced workload for contacting

Return ONLY this JSON structure:
{
  "prioritised_recalls": [
    {
      "recall_id": number,
      "priority_rank": number,
      "priority_level": "urgent|high|medium|low",
      "reason": "string explaining why this priority"
    }
  ],
  "summary": {
    "urgent_count": number,
    "high_count": number,
    "medium_count": number,
    "low_count": number
  },
  "recommended_contact_order": [number],
  "disclaimer": "AI-generated prioritisation for clinical workflow assistance only."
}
PROMPT;
    }

    // ── Response parser ───────────────────────────────────────────────────────

    private function extractResponseText(\Illuminate\Http\Client\Response $response): string
    {
        $parts = $response->json('candidates.0.content.parts', []);
        $text  = '';

        foreach ($parts as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $text .= $part['text'];
            }
        }

        return $text;
    }

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
