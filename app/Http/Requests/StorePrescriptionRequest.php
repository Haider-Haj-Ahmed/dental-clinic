<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'   => ['required', 'integer', 'exists:patients,id'],
            'encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
            'issued_at'    => ['sometimes', 'date'],
            'notes'        => ['nullable', 'string'],
            'items'        => ['required', 'array', 'min:1'],
            'items.*.drug_name'    => ['required', 'string', 'max:255'],
            'items.*.dose'         => ['nullable', 'string', 'max:100'],
            'items.*.frequency'    => ['nullable', 'string', 'max:100'],
            'items.*.duration'     => ['nullable', 'string', 'max:100'],
            'items.*.quantity'     => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string'],
        ];
    }
}
