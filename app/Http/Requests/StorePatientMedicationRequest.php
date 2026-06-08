<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientMedicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'drug_name'     => ['required', 'string', 'max:255'],
            'dose'          => ['nullable', 'string', 'max:100'],
            'frequency'     => ['nullable', 'string', 'max:100'],
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'prescribed_by' => ['nullable', 'integer', 'exists:providers,id'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
