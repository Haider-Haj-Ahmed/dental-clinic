<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientConsentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'consent_type'        => ['sometimes', 'required', 'string', 'max:255'],
            'signed_at'           => ['sometimes', 'nullable', 'date'],
            'signed_by_patient'   => ['sometimes', 'boolean'],
            'witness_provider_id' => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'file_path'           => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
