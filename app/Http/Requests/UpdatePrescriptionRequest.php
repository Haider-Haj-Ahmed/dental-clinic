<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes'      => ['sometimes', 'nullable', 'string'],
            'is_printed' => ['sometimes', 'boolean'],
        ];
    }
}
