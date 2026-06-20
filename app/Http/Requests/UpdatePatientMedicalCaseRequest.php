<?php

namespace App\Http\Requests;

use App\Models\PatientMedicalCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientMedicalCaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider_id'         => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'case_date'           => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'case_type'           => ['sometimes', 'required', 'string', Rule::in(PatientMedicalCase::TYPES)],
            'chief_complaint'     => ['sometimes', 'nullable', 'string', 'max:500'],
            'diagnosis'           => ['sometimes', 'nullable', 'string'],
            'treatment_performed' => ['sometimes', 'nullable', 'string'],
            'outcome'             => ['sometimes', 'nullable', 'string'],
            'is_external'         => ['sometimes', 'boolean'],
            'previous_clinic'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'previous_dentist'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes'               => ['sometimes', 'nullable', 'string'],
        ];
    }
}
