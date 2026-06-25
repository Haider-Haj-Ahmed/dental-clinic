<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'     => ['required', 'integer', 'exists:patients,id'],
            'provider_id'    => ['required', 'integer', 'exists:providers,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'encounter_date' => ['required', 'date', 'before_or_equal:today'],
            'subjective'     => ['nullable', 'string'],
            'objective'      => ['nullable', 'string'],
            'assessment'     => ['nullable', 'string'],
            'plan'           => ['nullable', 'string'],
        ];
    }
}
