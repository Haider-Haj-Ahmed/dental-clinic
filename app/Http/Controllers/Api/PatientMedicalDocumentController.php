<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientMedicalDocumentRequest;
use App\Http\Requests\UpdatePatientMedicalDocumentRequest;
use App\Http\Resources\PatientMedicalDocumentResource;
use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientMedicalDocumentController extends Controller
{
    public function __construct(private FileStorageService $storage) {}

    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PatientMedicalDocument::class);

        $documents = $patient->medicalDocuments()
            ->with(['provider', 'uploadedBy', 'medicalCase'])
            ->when($request->filled('document_type'), fn ($q) => $q->where('document_type', $request->string('document_type')))
            ->when($request->filled('medical_case_id'), fn ($q) => $q->where('medical_case_id', $request->integer('medical_case_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PatientMedicalDocumentResource::collection($documents);
    }

    public function store(StorePatientMedicalDocumentRequest $request, Patient $patient): PatientMedicalDocumentResource
    {
        $this->authorize('create', PatientMedicalDocument::class);

        $stored = $this->storage->store(
            $request->file('file'),
            'medical-documents/'.$patient->id
        );

        $payload = array_merge($request->safe()->except('file'), [
            'patient_id'  => $patient->id,
            'file_path'   => $stored['path'],
            'file_size_kb'=> $stored['size_kb'],
            'mime_type'   => $stored['mime_type'],
            'uploaded_by' => $request->user()->id,
        ]);

        // Auto-assign provider_id if uploader is a provider and none was given
        if ($request->user()->isProvider() && empty($payload['provider_id'])) {
            $payload['provider_id'] = $request->user()->providerProfile?->id;
        }

        $document = PatientMedicalDocument::query()->create($payload);

        return PatientMedicalDocumentResource::make(
            $document->load(['provider', 'uploadedBy', 'medicalCase'])
        );
    }

    public function show(Patient $patient, PatientMedicalDocument $document): PatientMedicalDocumentResource
    {
        $this->authorize('view', $document);
        abort_if($document->patient_id !== $patient->id, 404);

        return PatientMedicalDocumentResource::make(
            $document->load(['provider', 'uploadedBy', 'medicalCase'])
        );
    }

    public function update(UpdatePatientMedicalDocumentRequest $request, Patient $patient, PatientMedicalDocument $document): PatientMedicalDocumentResource
    {
        $this->authorize('update', $document);
        abort_if($document->patient_id !== $patient->id, 404);

        $document->update($request->validated());

        return PatientMedicalDocumentResource::make(
            $document->refresh()->load(['provider', 'uploadedBy', 'medicalCase'])
        );
    }

    public function destroy(Patient $patient, PatientMedicalDocument $document): Response
    {
        $this->authorize('delete', $document);
        abort_if($document->patient_id !== $patient->id, 404);

        $this->storage->delete($document->file_path);
        $document->delete();

        return $this->noContentResponse();
    }

    /**
     * GET /patients/{patient}/documents/{document}/download
     * Returns a short-lived URL to the file (30 min).
     */
    public function download(Patient $patient, PatientMedicalDocument $document): JsonResponse
    {
        $this->authorize('view', $document);
        abort_if($document->patient_id !== $patient->id, 404);

        abort_unless($this->storage->exists($document->file_path), 404, 'File not found on disk.');

        return $this->successResponse([
            'url'        => $this->storage->temporaryUrl($document->file_path),
            'expires_in' => 1800, // 30 minutes in seconds
            'mime_type'  => $document->mime_type,
            'title'      => $document->title,
        ]);
    }
}
