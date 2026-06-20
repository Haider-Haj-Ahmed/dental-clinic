<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientAllergyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'allergen' => ['sometimes', 'required', 'string', 'max:255'],
            'reaction' => ['sometimes', 'nullable', 'string', 'max:255'],
            'severity' => ['sometimes', 'nullable', 'string', Rule::in(['mild', 'moderate', 'severe'])],
            'noted_by' => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'noted_at' => ['sometimes', 'nullable', 'date'],
            'notes'    => ['sometimes', 'nullable', 'string'],
        ];
    }
}
