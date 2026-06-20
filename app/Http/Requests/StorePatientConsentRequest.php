<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientConsentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'consent_type'       => ['required', 'string', 'max:255'],
            'signed_at'          => ['nullable', 'date'],
            'signed_by_patient'  => ['sometimes', 'boolean'],
            'witness_provider_id'=> ['nullable', 'integer', 'exists:providers,id'],
            'file_path'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
