<?php

namespace App\Http\Requests;

use App\Models\PatientMedicalDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientMedicalDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'document_type'         => ['sometimes', 'required', 'string', Rule::in(PatientMedicalDocument::TYPES)],
            'title'                 => ['sometimes', 'required', 'string', 'max:255'],
            'description'           => ['sometimes', 'nullable', 'string'],
            'medical_case_id'       => ['sometimes', 'nullable', 'integer', 'exists:patient_medical_cases,id'],
            'provider_id'           => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'taken_at'              => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'external_source'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_visible_to_patient' => ['sometimes', 'boolean'],
        ];
    }
}
