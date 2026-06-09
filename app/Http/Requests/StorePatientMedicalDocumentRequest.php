<?php

namespace App\Http\Requests;

use App\Models\PatientMedicalDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientMedicalDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file'                  => ['required', 'file', 'max:20480', // 20MB
                                        'mimes:jpg,jpeg,png,pdf,dcm,doc,docx'],
            'document_type'         => ['required', 'string', Rule::in(PatientMedicalDocument::TYPES)],
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'medical_case_id'       => ['nullable', 'integer', 'exists:patient_medical_cases,id'],
            'provider_id'           => ['nullable', 'integer', 'exists:providers,id'],
            'taken_at'              => ['nullable', 'date', 'before_or_equal:today'],
            'external_source'       => ['nullable', 'string', 'max:255'],
            'is_visible_to_patient' => ['sometimes', 'boolean'],
        ];
    }
}
