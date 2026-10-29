<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEncounterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'     => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'provider_id'    => ['required', 'integer', 'exists:providers,id'],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('patient_id', $this->input('patient_id'))],
            'encounter_date' => ['required', 'date', 'before_or_equal:today'],
            'subjective'     => ['nullable', 'string'],
            'objective'      => ['nullable', 'string'],
            'assessment'     => ['nullable', 'string'],
            'plan'           => ['nullable', 'string'],
        ];
    }
}
