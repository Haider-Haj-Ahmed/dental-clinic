<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'        => ['sometimes', 'required', 'string', 'max:50'],
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'phone'        => ['sometimes', 'nullable', 'string', 'max:30'],
            'relationship' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_emergency' => ['sometimes', 'boolean'],
        ];
    }
}
