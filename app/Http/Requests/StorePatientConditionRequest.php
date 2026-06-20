<?php

namespace App\Http\Requests;

use App\Models\PatientCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientConditionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'condition'  => ['required', 'string', 'max:255'],
            'icd_code'   => ['nullable', 'string', 'max:20'],
            'status'     => ['sometimes', 'string', Rule::in([PatientCondition::STATUS_ACTIVE, PatientCondition::STATUS_RESOLVED])],
            'onset_date' => ['nullable', 'date'],
            'noted_by'   => ['nullable', 'integer', 'exists:providers,id'],
            'noted_at'   => ['nullable', 'date'],
            'notes'      => ['nullable', 'string'],
        ];
    }
}
