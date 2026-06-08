<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientMedicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'drug_name'     => ['sometimes', 'required', 'string', 'max:255'],
            'dose'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'frequency'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_date'    => ['sometimes', 'nullable', 'date'],
            'end_date'      => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'prescribed_by' => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'notes'         => ['sometimes', 'nullable', 'string'],
        ];
    }
}
