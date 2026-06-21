<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiAnalysisResultResource;
use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Services\AiService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AiAnalysisController extends Controller
{
    public function __construct(
        private readonly AiService $aiService,
        private readonly FileStorageService $fileStorageService,
    ) {}

    // ── Phase 5A — X-ray / image analysis ────────────────────────────────────

    /**
     * POST /patients/{patient}/documents/{document}/analyze
     */
    public function analyzeDocument(Request $request, Patient $patient, PatientMedicalDocument $document): AiAnalysisResultResource
    {
        $this->authorize('create', AiAnalysisResult::class);

        abort_if($document->patient_id !== $patient->id, 404);

        $allowedTypes = config('ai.allowed_image_types', []);
        abort_if(
            ! in_array($document->mime_type, $allowedTypes, true),
            422,
            "Document type '{$document->mime_type}' is not supported for AI analysis. Supported: ".implode(', ', $allowedTypes)
        );

        abort_unless(
            $this->fileStorageService->exists($document->file_path),
            422,
            'The document file could not be found on disk.'
        );

        // Return existing pending result instead of creating a duplicate
        $existing = AiAnalysisResult::query()
            ->where('source_type', 'document')
            ->where('source_id', $document->id)
            ->where('status', AiAnalysisResult::STATUS_PENDING)
            ->first();

        if ($existing) {
            return AiAnalysisResultResource::make($existing->load(['requestedBy', 'reviewedBy']));
        }

        $result = $this->aiService->analyseImage($document, $request->user(), $document->file_path);

        return AiAnalysisResultResource::make($result->load(['requestedBy', 'reviewedBy']));
    }

    // ── Phase 5B — SOAP note suggestion ──────────────────────────────────────

    /**
     * POST /encounters/{encounter}/suggest-soap
     */
    public function suggestSoap(Request $request, Encounter $encounter): AiAnalysisResultResource
    {
        $this->authorize('create', AiAnalysisResult::class);

        abort_if($encounter->is_locked, 422, 'Cannot generate SOAP suggestion for a locked encounter.');

        // Return existing pending suggestion instead of creating a duplicate
        $existing = AiAnalysisResult::query()
            ->where('source_type', 'encounter')
            ->where('source_id', $encounter->id)
            ->where('analysis_type', AiAnalysisResult::TYPE_SOAP_SUGGESTION)
            ->where('status', AiAnalysisResult::STATUS_PENDING)
            ->first();

        if ($existing) {
            return AiAnalysisResultResource::make($existing->load(['requestedBy', 'reviewedBy']));
        }

        $result = $this->aiService->suggestSoap($encounter, $request->user());

        return AiAnalysisResultResource::make($result->load(['requestedBy', 'reviewedBy']));
    }

    /**
     * POST /ai-results/{result}/apply-soap
     * Provider accepts the SOAP suggestion and writes it back to the encounter.
     * Only works for soap_suggestion type results.
     */
    public function applySoap(Request $request, AiAnalysisResult $result): AiAnalysisResultResource
    {
        $this->authorize('review', $result);

        abort_if($result->analysis_type !== AiAnalysisResult::TYPE_SOAP_SUGGESTION, 422, 'This action is only valid for SOAP suggestions.');
        abort_if($result->status !== AiAnalysisResult::STATUS_PENDING, 422, 'Only pending suggestions can be applied.');

        $request->validate([
            'reviewer_notes' => ['nullable', 'string'],
            // Provider can override individual SOAP fields before applying
            'subjective'     => ['sometimes', 'nullable', 'string'],
            'objective'      => ['sometimes', 'nullable', 'string'],
            'assessment'     => ['sometimes', 'nullable', 'string'],
            'plan'           => ['sometimes', 'nullable', 'string'],
        ]);

        $encounter = Encounter::findOrFail($result->source_id);

        abort_if($encounter->is_locked, 422, 'Cannot apply suggestion to a locked encounter.');

        // Use provider-overridden values if supplied, else use AI suggestion
        $aiResult = $result->result;

        $encounter->update([
            'subjective' => $request->input('subjective', $aiResult['subjective'] ?? $encounter->subjective),
            'objective'  => $request->input('objective',  $aiResult['objective']  ?? $encounter->objective),
            'assessment' => $request->input('assessment', $aiResult['assessment'] ?? $encounter->assessment),
            'plan'       => $request->input('plan',       $aiResult['plan']       ?? $encounter->plan),
        ]);

        $result->update([
            'status'         => AiAnalysisResult::STATUS_ACCEPTED,
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'reviewer_notes' => $request->input('reviewer_notes'),
        ]);

        return AiAnalysisResultResource::make($result->refresh()->load(['requestedBy', 'reviewedBy']));
    }

    // ── Shared list / show / review ───────────────────────────────────────────

    public function patientResults(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AiAnalysisResult::class);

        $results = AiAnalysisResult::query()
            ->where('patient_id', $patient->id)
            ->with(['requestedBy', 'reviewedBy'])
            ->when($request->filled('analysis_type'), fn ($q) => $q->where('analysis_type', $request->string('analysis_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return AiAnalysisResultResource::collection($results);
    }

    public function show(AiAnalysisResult $result): AiAnalysisResultResource
    {
        $this->authorize('view', $result);

        return AiAnalysisResultResource::make($result->load(['requestedBy', 'reviewedBy']));
    }

    public function accept(Request $request, AiAnalysisResult $result): AiAnalysisResultResource
    {
        $this->authorize('review', $result);

        abort_if($result->status !== AiAnalysisResult::STATUS_PENDING, 422, 'Only pending results can be accepted.');

        $request->validate([
            'reviewer_notes' => ['nullable', 'string'],
        ]);

        $result->update([
            'status'         => AiAnalysisResult::STATUS_ACCEPTED,
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'reviewer_notes' => $request->input('reviewer_notes'),
        ]);

        return AiAnalysisResultResource::make($result->refresh()->load(['requestedBy', 'reviewedBy']));
    }

    public function dismiss(Request $request, AiAnalysisResult $result): AiAnalysisResultResource
    {
        $this->authorize('review', $result);

        abort_if($result->status !== AiAnalysisResult::STATUS_PENDING, 422, 'Only pending results can be dismissed.');

        $request->validate([
            'reviewer_notes' => ['nullable', 'string'],
        ]);

        $result->update([
            'status'         => AiAnalysisResult::STATUS_DISMISSED,
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'reviewer_notes' => $request->input('reviewer_notes'),
        ]);

        return AiAnalysisResultResource::make($result->refresh()->load(['requestedBy', 'reviewedBy']));
    }
}
