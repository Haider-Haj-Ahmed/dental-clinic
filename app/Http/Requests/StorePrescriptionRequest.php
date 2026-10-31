<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'   => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'encounter_id' => ['nullable', 'integer', Rule::exists('encounters', 'id')->where('patient_id', $this->input('patient_id'))],
            'provider_id'  => ['nullable', 'integer', 'exists:providers,id'],
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
