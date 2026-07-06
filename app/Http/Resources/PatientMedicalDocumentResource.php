<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PatientMedicalDocumentResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'patient_id'            => $this->patient_id,
            'medical_case_id'       => $this->medical_case_id,
            'provider_id'           => $this->provider_id,
            'document_type'         => $this->document_type,
            'title'                 => $this->title,
            'description'           => $this->description,
            'file_size_kb'          => $this->file_size_kb,
            'mime_type'             => $this->mime_type,
            'taken_at'              => $this->taken_at?->toDateString(),
            'external_source'       => $this->external_source,
            'is_visible_to_patient' => $this->is_visible_to_patient,
            'uploaded_by'           => $this->uploaded_by,
            'provider'              => ProviderResource::make($this->whenLoaded('provider')),
            'uploader'              => UserResource::make($this->whenLoaded('uploadedBy')),
            'medical_case'          => PatientMedicalCaseResource::make($this->whenLoaded('medicalCase')),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
            // file_path intentionally excluded — clients use the download endpoint
        ];
    }
}
