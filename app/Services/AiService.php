<?php

namespace App\Services;

use App\Models\AiAnalysisResult;
use App\Models\PatientMedicalDocument;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $provider;
    private string $model;
    private string $apiKey;

    public function __construct()
    {
        $this->provider = config('ai.default_provider', 'anthropic');
        $this->model    = config('ai.models.vision', 'claude-haiku-4-5-20251001');
        $this->apiKey   = config('ai.api_key', '');
    }

    /**
     * Analyse a dental X-ray or clinical image.
     * Sends the image to the AI vision model and returns structured findings.
     */
    public function analyseImage(
        PatientMedicalDocument $document,
        User $requestedBy,
        string $filePath,
    ): AiAnalysisResult {
        $prompt = $this->buildXrayPrompt($document);

        $rawResult = $this->callVisionApi($filePath, $document->mime_type, $prompt);

        return AiAnalysisResult::create([
            'patient_id'    => $document->patient_id,
            'requested_by'  => $requestedBy->id,
            'source_type'   => 'document',
            'source_id'     => $document->id,
            'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            'ai_provider'   => $this->provider,
            'ai_model'      => $this->model,
            'input_summary' => "Document #{$document->id} — {$document->document_type} — {$document->title}",
            'result'        => $rawResult,
            'status'        => AiAnalysisResult::STATUS_PENDING,
        ]);
    }

    /**
     * Call Anthropic Claude with an image attachment.
     */
    private function callVisionApi(string $filePath, string $mimeType, string $prompt): array
    {
        // Read image and base64-encode it
        $imageData = base64_encode(file_get_contents(storage_path('app/'.$filePath)));

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 1024,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'   => 'image',
                                'source' => [
                                    'type'       => 'base64',
                                    'media_type' => $mimeType,
                                    'data'       => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'system' => $this->systemPrompt(),
            ]);

        if ($response->failed()) {
            Log::error('AI vision API failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \RuntimeException('AI analysis failed: '.$response->body());
        }

        $text = $response->json('content.0.text', '');

        return $this->parseStructuredResponse($text);
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an AI assistant supporting a licensed dentist. Your role is to analyse dental radiographs and clinical images and provide structured observations to help the dentist in their clinical assessment.

CRITICAL RULES:
- You ONLY provide observations and suggestions — the licensed dentist makes all clinical decisions
- Always flag confidence level for each finding (high/medium/low)
- Always include a disclaimer that findings require clinical verification
- Never diagnose — you observe and suggest for the dentist to review
- If the image quality is insufficient for analysis, say so clearly
PROMPT;
    }

    private function buildXrayPrompt(PatientMedicalDocument $document): string
    {
        $type = $document->document_type;
        $takenAt = $document->taken_at?->toDateString() ?? 'unknown date';

        return <<<PROMPT
Please analyse this dental {$type} taken on {$takenAt}.

Provide your response as a JSON object with exactly this structure:
{
  "image_quality": "good|acceptable|poor",
  "image_quality_notes": "any notes about image quality",
  "findings": [
    {
      "tooth_number": "FDI notation or null if not tooth-specific",
      "region": "description of region (e.g. upper left quadrant)",
      "observation": "what you observe",
      "possible_conditions": ["list", "of", "possible", "conditions"],
      "confidence": "high|medium|low",
      "urgency": "routine|monitor|prompt|urgent"
    }
  ],
  "overall_assessment": "brief overall summary",
  "recommendations": ["list of suggested follow-up actions for the dentist to consider"],
  "disclaimer": "These are AI-generated observations for clinical review only. All findings must be verified by the treating dentist."
}

Return ONLY the JSON object, no additional text.
PROMPT;
    }

    private function parseStructuredResponse(string $text): array
    {
        // Strip any markdown code fences if present
        $text = preg_replace('/^```json\s*/m', '', $text);
        $text = preg_replace('/^```\s*/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            // Fallback — store raw text so nothing is lost
            return [
                'parse_error'    => true,
                'raw_response'   => $text,
                'findings'       => [],
                'overall_assessment' => 'Response could not be parsed. See raw_response.',
                'disclaimer'     => 'AI-generated observations require clinical verification.',
            ];
        }

        return $decoded;
    }
}
