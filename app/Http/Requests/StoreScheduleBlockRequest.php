<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleBlockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider_id'  => ['nullable', 'required_without:operatory_id', 'integer', 'exists:providers,id'],
            'operatory_id' => ['nullable', 'required_without:provider_id', 'integer', 'exists:operatories,id'],
            'start_at'     => ['required', 'date'],
            'end_at'       => ['required', 'date', 'after:start_at'],
            'reason'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider_id.required_without'  => 'At least one of provider or operatory must be specified.',
            'operatory_id.required_without' => 'At least one of provider or operatory must be specified.',
        ];
    }
}
