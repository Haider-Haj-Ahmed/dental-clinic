<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'        => ['nullable', 'integer', 'exists:users,id', 'unique:providers,user_id'],
            'operatory_id'   => ['nullable', 'integer', 'exists:operatories,id'],
            'name'           => ['required', 'string', 'max:255'],
            'specialty'      => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100', 'unique:providers,license_number'],
            'bio'            => ['nullable', 'string'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
