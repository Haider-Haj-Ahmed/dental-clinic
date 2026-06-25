<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEncounterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'encounter_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'subjective'     => ['sometimes', 'nullable', 'string'],
            'objective'      => ['sometimes', 'nullable', 'string'],
            'assessment'     => ['sometimes', 'nullable', 'string'],
            'plan'           => ['sometimes', 'nullable', 'string'],
        ];
    }
}
