<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone'        => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'      => ['sometimes', 'nullable', 'string'],
            'notes'        => ['sometimes', 'nullable', 'string'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }
}
