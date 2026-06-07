<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'              => ['required', 'string', 'max:100'],
            'last_name'               => ['required', 'string', 'max:100'],
            'gender'                  => ['nullable', 'in:male,female,other'],
            'date_of_birth'           => ['nullable', 'date', 'before_or_equal:today'],
            'phone'                   => ['nullable', 'string', 'max:30'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'address'                 => ['nullable', 'string'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'notes'                   => ['nullable', 'string'],
            'is_active'               => ['sometimes', 'boolean'],
        ];
    }
}
