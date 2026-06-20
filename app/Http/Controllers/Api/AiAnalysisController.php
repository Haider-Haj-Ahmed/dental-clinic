<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiAnalysisResultResource;
use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Services\AiService;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AiAnalysisController extends Controller
{
    public function __construct(
        private readonly AiService $aiService,
        private readonly FileStorageService $fileStorageService,
    ) {}

    /**
     * POST /patients/{patient}/documents/{document}/analyze
     * Trigger AI analysis on an X-ray or clinical image.
     */
    public function analyzeDocument(Request $request, Patient $patient, PatientMedicalDocument $document): AiAnalysisResultResource
    {
        $this->authorize('create', AiAnalysisResult::class);

        abort_if($document->patient_id !== $patient->id, 404);

        // Only image types can be analysed
        $allowedTypes = config('ai.allowed_image_types', []);
        abort_if(
            ! in_array($document->mime_type, $allowedTypes, true),
            422,
            "Document type '{$document->mime_type}' is not supported for AI analysis. Supported types: ".implode(', ', $allowedTypes)
        );

        // Check the file actually exists on disk
        abort_unless(
            $this->fileStorageService->exists($document->file_path),
            422,
            'The document file could not be found on disk.'
        );

        // Check for an existing pending analysis to avoid duplicates
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

    /**
     * GET /patients/{patient}/ai-results
     * List all AI analysis results for a patient.
     */
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

    /**
     * GET /ai-results/{result}
     * Show a single AI analysis result.
     */
    public function show(AiAnalysisResult $result): AiAnalysisResultResource
    {
        $this->authorize('view', $result);

        return AiAnalysisResultResource::make($result->load(['requestedBy', 'reviewedBy']));
    }

    /**
     * POST /ai-results/{result}/accept
     * Provider explicitly accepts the AI suggestion — records who reviewed it.
     */
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

    /**
     * POST /ai-results/{result}/dismiss
     * Provider dismisses the AI suggestion.
     */
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
