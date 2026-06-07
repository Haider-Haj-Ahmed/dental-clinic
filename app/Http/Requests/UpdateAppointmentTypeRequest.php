<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                     => ['sometimes', 'required', 'string', 'max:100'],
            'default_duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'color'                    => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'                => ['sometimes', 'boolean'],
        ];
    }
}
