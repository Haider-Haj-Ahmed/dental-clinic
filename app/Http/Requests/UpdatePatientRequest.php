<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'              => ['sometimes', 'required', 'string', 'max:100'],
            'last_name'               => ['sometimes', 'required', 'string', 'max:100'],
            'gender'                  => ['sometimes', 'nullable', 'in:male,female,other'],
            'date_of_birth'           => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'phone'                   => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'                   => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'                 => ['sometimes', 'nullable', 'string'],
            'emergency_contact_name'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'notes'                   => ['sometimes', 'nullable', 'string'],
            'is_active'               => ['sometimes', 'boolean'],
        ];
    }
}
