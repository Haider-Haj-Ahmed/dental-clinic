<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'        => ['required', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'is_emergency' => ['sometimes', 'boolean'],
        ];
    }
}
