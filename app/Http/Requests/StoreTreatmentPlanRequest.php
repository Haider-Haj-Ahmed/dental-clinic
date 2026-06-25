<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'  => ['required', 'integer', 'exists:patients,id'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'title'       => ['required', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
            'items'       => ['sometimes', 'array'],
            'items.*.procedure_code_id' => ['nullable', 'integer', 'exists:procedure_codes,id'],
            'items.*.tooth_number'      => ['nullable', 'integer', 'min:11', 'max:85'],
            'items.*.surface'           => ['nullable', 'string', 'max:10'],
            'items.*.description'       => ['required', 'string', 'max:255'],
            'items.*.fee'               => ['required', 'numeric', 'min:0'],
            'items.*.sort_order'        => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
