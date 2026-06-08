<?php

namespace App\Http\Requests;

use App\Models\PatientMedicalCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientMedicalCaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider_id'         => ['nullable', 'integer', 'exists:providers,id'],
            'case_date'           => ['required', 'date', 'before_or_equal:today'],
            'case_type'           => ['required', 'string', Rule::in(PatientMedicalCase::TYPES)],
            'chief_complaint'     => ['nullable', 'string', 'max:500'],
            'diagnosis'           => ['nullable', 'string'],
            'treatment_performed' => ['nullable', 'string'],
            'outcome'             => ['nullable', 'string'],
            'is_external'         => ['sometimes', 'boolean'],
            'previous_clinic'     => ['nullable', 'string', 'max:255', 'required_if:is_external,true'],
            'previous_dentist'    => ['nullable', 'string', 'max:255'],
            'notes'               => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'previous_clinic.required_if' => 'The clinic name is required when recording an external case.',
        ];
    }
}
