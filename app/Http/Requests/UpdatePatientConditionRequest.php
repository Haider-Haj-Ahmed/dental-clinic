<?php

namespace App\Http\Requests;

use App\Models\PatientCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientConditionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'condition'  => ['sometimes', 'required', 'string', 'max:255'],
            'icd_code'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'status'     => ['sometimes', 'string', Rule::in([PatientCondition::STATUS_ACTIVE, PatientCondition::STATUS_RESOLVED])],
            'onset_date' => ['sometimes', 'nullable', 'date'],
            'noted_by'   => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'noted_at'   => ['sometimes', 'nullable', 'date'],
            'notes'      => ['sometimes', 'nullable', 'string'],
        ];
    }
}
