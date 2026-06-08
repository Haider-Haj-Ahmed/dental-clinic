<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientAllergyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'allergen' => ['required', 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', Rule::in(['mild', 'moderate', 'severe'])],
            'noted_by' => ['nullable', 'integer', 'exists:providers,id'],
            'noted_at' => ['nullable', 'date'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
