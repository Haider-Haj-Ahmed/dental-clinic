<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisResult;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PerioExam;
use App\Models\PatientMedicalDocument;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Recall;
use App\Services\AiService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Web\AiAssistantController
 *
 * Mirrors every action in Api\AiAnalysisController but returns
 * Blade views / redirects instead of JSON responses.
 *
 * The API controller is NEVER touched — this controller calls
 * the same Services and Models directly.
 *
 * Routes (routes/web.php):
 *   GET    /dashboard/ai                                            → index
 *   GET    /dashboard/ai/{result}                                   → show (HTMX swap)
 *   PATCH  /dashboard/ai/{result}/accept                            → accept
 *   PATCH  /dashboard/ai/{result}/dismiss                           → dismiss
 *   POST   /dashboard/ai/{result}/apply-soap                        → applySoap
 *   POST   /dashboard/ai/{result}/create-prescription               → createPrescription
 *   POST   /dashboard/patients/{patient}/documents/{document}/analyze → analyzeDocument
 *   POST   /dashboard/encounters/{encounter}/suggest-soap            → suggestSoap
 *   POST   /dashboard/patients/{patient}/prescription-suggestions    → suggestPrescription
 *   POST   /dashboard/perio-exams/{perioExam}/risk-score             → scorePerioRisk
 *   GET    /dashboard/patients/{patient}/ai-insights                 → patientInsights
 *   GET    /dashboard/patients/{patient}/ai-results                  → patientResults
 *   POST   /dashboard/ai/recalls/prioritize                         → prioritiseRecalls
 */
class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiService $aiService,
        private readonly FileStorageService $fileStorageService,
    ) {}

    /* ── Type filter map (URL param → DB column value) ─────── */
    private const TYPE_MAP = [
        'xray'         => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
        'soap'         => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
        'prescription' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
        'perio'        => AiAnalysisResult::TYPE_PERIO_RISK,
    ];

    /* ════════════════════════════════════════════════════════════
     * LISTING & DISPLAY
     * ════════════════════════════════════════════════════════════ */

    /**
     * Main AI assistant page — lists all results with filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AiAnalysisResult::class);

        $type   = $request->query('type', 'all');
        $status = $request->query('status', 'all');

        $query = AiAnalysisResult::with(['requestedBy', 'reviewedBy', 'patient'])
            ->latest();

        if ($type !== 'all' && isset(self::TYPE_MAP[$type])) {
            $query->where('analysis_type', self::TYPE_MAP[$type]);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $results = $query->paginate(12)->withQueryString();

        $counts = [
            'all'          => AiAnalysisResult::count(),
            'xray'         => AiAnalysisResult::where('analysis_type', AiAnalysisResult::TYPE_XRAY_ANALYSIS)->count(),
            'soap'         => AiAnalysisResult::where('analysis_type', AiAnalysisResult::TYPE_SOAP_SUGGESTION)->count(),
            'prescription' => AiAnalysisResult::where('analysis_type', AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION)->count(),
            'perio'        => AiAnalysisResult::where('analysis_type', AiAnalysisResult::TYPE_PERIO_RISK)->count(),
        ];

        $selected = $results->first()?->load(['requestedBy', 'reviewedBy', 'patient']);

        return view('web.ai.index', compact('results', 'activeType', 'activeStatus', 'selected', 'counts') + [
            'activeType'   => $type,
            'activeStatus' => $status,
        ]);
    }

    /**
     * Detail panel — returned as partial for HTMX, redirect otherwise.
     */
    public function show(AiAnalysisResult $result): mixed
    {
        $this->authorize('view', $result);

        $result->load(['requestedBy', 'reviewedBy', 'patient']);

        if (request()->header('HX-Request')) {
            return view('web.ai._detail', compact('result'));
        }

        return redirect()->route('web.ai', ['selected' => $result->id]);
    }

    /**
     * Per-patient AI results list (used by patient profile page).
     */
    public function patientResults(Request $request, Patient $patient): View
    {
        $this->authorize('viewAny', AiAnalysisResult::class);

        $results = AiAnalysisResult::query()
            ->where('patient_id', $patient->id)
            ->with(['requestedBy', 'reviewedBy'])
            ->when($request->filled('analysis_type'), fn ($q) => $q->where('analysis_type', $request->string('analysis_type')))
            ->when($request->filled('status'),        fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('web.ai.patient-results', compact('patient', 'results'));
    }

    /**
     * Aggregate AI insights per patient (used by patient profile sidebar).
     */
    public function patientInsights(Patient $patient): View
    {
        $this->authorize('viewAny', AiAnalysisResult::class);

        $types = [
            AiAnalysisResult::TYPE_XRAY_ANALYSIS,
            AiAnalysisResult::TYPE_SOAP_SUGGESTION,
            AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
            AiAnalysisResult::TYPE_PERIO_RISK,
        ];

        $insights = [];
        foreach ($types as $type) {
            $insights[$type] = AiAnalysisResult::query()
                ->where('patient_id', $patient->id)
                ->where('analysis_type', $type)
                ->with(['requestedBy'])
                ->latest()
                ->first();
        }

        $latestPerio = $insights[AiAnalysisResult::TYPE_PERIO_RISK];
        $riskLevel   = $latestPerio?->result['risk_level'] ?? null;
        $riskScore   = $latestPerio?->result['risk_score'] ?? null;

        return view('web.ai.patient-insights', compact('patient', 'insights', 'riskLevel', 'riskScore'));
    }

    /* ════════════════════════════════════════════════════════════
     * TRIGGER ACTIONS (generate new AI results)
     * ════════════════════════════════════════════════════════════ */

    /**
     * Trigger X-ray / image analysis on a patient document.
     * Mirrors: Api\AiAnalysisController@analyzeDocument
     */
    public function analyzeDocument(Request $request, Patient $patient, PatientMedicalDocument $document): mixed
    {
        $this->authorize('create', AiAnalysisResult::class);

        abort_if($document->patient_id !== $patient->id, 404);

        $allowedTypes = config('ai.allowed_image_types', []);
        abort_if(
            ! in_array($document->mime_type, $allowedTypes, true),
            422,
            "Document type '{$document->mime_type}' is not supported for AI analysis."
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
            return $this->redirectToResult($existing, 'Analysis already in progress.');
        }

        $result = $this->aiService->analyseImage($document, $request->user(), $document->file_path);

        return $this->redirectToResult($result, 'X-ray analysis started.');
    }

    /**
     * Trigger SOAP note suggestion for an encounter.
     * Mirrors: Api\AiAnalysisController@suggestSoap
     */
    public function suggestSoap(Request $request, Encounter $encounter): mixed
    {
        $this->authorize('create', AiAnalysisResult::class);

        abort_if($encounter->is_locked, 422, 'Cannot generate SOAP suggestion for a locked encounter.');

        $existing = AiAnalysisResult::query()
            ->where('source_type', 'encounter')
            ->where('source_id', $encounter->id)
            ->where('analysis_type', AiAnalysisResult::TYPE_SOAP_SUGGESTION)
            ->where('status', AiAnalysisResult::STATUS_PENDING)
            ->first();

        if ($existing) {
            return $this->redirectToResult($existing, 'SOAP suggestion already pending.');
        }

        $result = $this->aiService->suggestSoap($encounter, $request->user());

        return $this->redirectToResult($result, 'SOAP suggestion generated.');
    }

    /**
     * Trigger prescription suggestion for a patient.
     * Mirrors: Api\AiAnalysisController@suggestPrescription
     */
    public function suggestPrescription(Request $request, Patient $patient): mixed
    {
        $this->authorize('create', AiAnalysisResult::class);

        $request->validate([
            'diagnosis'    => ['required', 'string', 'min:10', 'max:1000'],
            'encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
        ]);

        $diagnosis   = $request->string('diagnosis')->toString();
        $encounterId = $request->integer('encounter_id') ?: null;

        $existing = AiAnalysisResult::query()
            ->where('patient_id', $patient->id)
            ->where('analysis_type', AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION)
            ->where('status', AiAnalysisResult::STATUS_PENDING)
            ->where('input_summary', "Patient #{$patient->id} — diagnosis: {$diagnosis}")
            ->first();

        if ($existing) {
            return $this->redirectToResult($existing, 'Prescription suggestion already pending.');
        }

        $result = $this->aiService->suggestPrescription(
            $patient,
            $diagnosis,
            $request->user(),
            $encounterId,
        );

        return $this->redirectToResult($result, 'Prescription suggestion generated.');
    }

    /**
     * Trigger perio risk scoring for a perio exam.
     * Mirrors: Api\AiAnalysisController@scorePerioRisk
     */
    public function scorePerioRisk(Request $request, PerioExam $perioExam): mixed
    {
        $this->authorize('create', AiAnalysisResult::class);

        abort_if(
            $perioExam->measures()->count() === 0,
            422,
            'Cannot score risk on a perio exam with no measurements recorded.'
        );

        $existing = AiAnalysisResult::query()
            ->where('source_type', 'encounter')
            ->where('source_id', $perioExam->id)
            ->where('analysis_type', AiAnalysisResult::TYPE_PERIO_RISK)
            ->where('status', AiAnalysisResult::STATUS_PENDING)
            ->first();

        if ($existing) {
            return $this->redirectToResult($existing, 'Perio risk score already pending.');
        }

        $result = $this->aiService->scorePerioRisk($perioExam, $request->user());

        return $this->redirectToResult($result, 'Perio risk score generated.');
    }

    /**
     * Trigger AI recall prioritisation.
     * Mirrors: Api\AiAnalysisController@prioritiseRecalls
     */
    public function prioritiseRecalls(Request $request): mixed
    {
        $this->authorize('create', AiAnalysisResult::class);

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $recalls = Recall::query()
            ->with('patient')
            ->whereIn('status', [Recall::STATUS_PENDING, Recall::STATUS_SENT])
            ->whereDate('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->limit($request->integer('limit', 50))
            ->get();

        abort_if($recalls->isEmpty(), 422, 'No pending recalls due in the next 30 days.');

        $result = $this->aiService->prioritiseRecalls($recalls, $request->user());

        return $this->redirectToResult($result, 'Recall prioritisation complete.');
    }

    /* ════════════════════════════════════════════════════════════
     * REVIEW ACTIONS (accept / dismiss / apply)
     * ════════════════════════════════════════════════════════════ */

    /**
     * Accept a result (generic — no encounter write-back).
     * Mirrors: Api\AiAnalysisController@accept
     */
    public function accept(Request $request, AiAnalysisResult $result): mixed
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

        return $this->panelOrRedirect($request, $result, 'Result accepted.');
    }

    /**
     * Apply a SOAP suggestion — writes fields back to the encounter.
     * Mirrors: Api\AiAnalysisController@applySoap
     */
    public function applySoap(Request $request, AiAnalysisResult $result): mixed
    {
        $this->authorize('review', $result);

        abort_if($result->analysis_type !== AiAnalysisResult::TYPE_SOAP_SUGGESTION, 422, 'This action is only valid for SOAP suggestions.');
        abort_if($result->status !== AiAnalysisResult::STATUS_PENDING, 422, 'Only pending suggestions can be applied.');

        $request->validate([
            'reviewer_notes' => ['nullable', 'string'],
            'subjective'     => ['sometimes', 'nullable', 'string'],
            'objective'      => ['sometimes', 'nullable', 'string'],
            'assessment'     => ['sometimes', 'nullable', 'string'],
            'plan'           => ['sometimes', 'nullable', 'string'],
        ]);

        $encounter = Encounter::findOrFail($result->source_id);
        abort_if($encounter->is_locked, 422, 'Cannot apply suggestion to a locked encounter.');

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

        return $this->panelOrRedirect($request, $result->refresh(), 'SOAP applied to encounter.');
    }

    /**
     * Accept a prescription suggestion and create a real prescription record.
     * Mirrors: Api\AiAnalysisController@createPrescription
     */
    public function createPrescription(Request $request, AiAnalysisResult $result): mixed
    {
        $this->authorize('review', $result);

        abort_if($result->analysis_type !== AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION, 422, 'This action is only valid for prescription suggestions.');
        abort_if($result->status !== AiAnalysisResult::STATUS_PENDING, 422, 'Only pending suggestions can be used to create a prescription.');

        $request->validate([
            'encounter_id'         => ['nullable', 'integer', 'exists:encounters,id'],
            'notes'                => ['nullable', 'string'],
            'reviewer_notes'       => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.drug_name'    => ['required', 'string', 'max:255'],
            'items.*.dose'         => ['nullable', 'string', 'max:100'],
            'items.*.frequency'    => ['nullable', 'string', 'max:100'],
            'items.*.duration'     => ['nullable', 'string', 'max:100'],
            'items.*.quantity'     => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string'],
        ]);

        $provider = $request->user()->providerProfile;
        abort_if(! $provider, 422, 'A provider profile is required to issue a prescription.');

        DB::transaction(function () use ($request, $result, $provider) {
            $prescription = Prescription::create([
                'patient_id'   => $result->patient_id,
                'provider_id'  => $provider->id,
                'encounter_id' => $request->integer('encounter_id') ?: null,
                'issued_at'    => now(),
                'notes'        => $request->input('notes'),
            ]);

            foreach ($request->input('items') as $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'drug_name'       => $item['drug_name'],
                    'dose'            => $item['dose']         ?? null,
                    'frequency'       => $item['frequency']    ?? null,
                    'duration'        => $item['duration']     ?? null,
                    'quantity'        => $item['quantity']     ?? null,
                    'instructions'    => $item['instructions'] ?? null,
                ]);
            }

            $result->update([
                'status'         => AiAnalysisResult::STATUS_ACCEPTED,
                'reviewed_by'    => $request->user()->id,
                'reviewed_at'    => now(),
                'reviewer_notes' => $request->input('reviewer_notes'),
            ]);
        });

        return $this->panelOrRedirect($request, $result->refresh(), 'Prescription created successfully.');
    }

    /**
     * Dismiss a result.
     * Mirrors: Api\AiAnalysisController@dismiss
     */
    public function dismiss(Request $request, AiAnalysisResult $result): mixed
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

        return $this->panelOrRedirect($request, $result, 'Result dismissed.');
    }

    /* ════════════════════════════════════════════════════════════
     * PRIVATE HELPERS
     * ════════════════════════════════════════════════════════════ */

    /**
     * After a trigger action: if HTMX request return the panel partial,
     * otherwise redirect to the AI listing with the new result selected.
     */
    private function redirectToResult(AiAnalysisResult $result, string $message): mixed
    {
        $result->load(['requestedBy', 'reviewedBy', 'patient']);

        if (request()->header('HX-Request')) {
            return view('web.ai._detail', compact('result'));
        }

        return redirect()
            ->route('web.ai', ['selected' => $result->id])
            ->with('success', $message);
    }

    /**
     * After a review action: swap detail panel for HTMX, or redirect back.
     */
    private function panelOrRedirect(Request $request, AiAnalysisResult $result, string $message): mixed
    {
        $result->load(['requestedBy', 'reviewedBy', 'patient']);

        if ($request->header('HX-Request')) {
            return view('web.ai._detail', compact('result'));
        }

        return back()->with('success', $message);
    }
}
