<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'          => ['required', 'integer',
                                       Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'provider_id'         => ['required', 'integer', 'exists:providers,id'],
            'appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
            'operatory_id'        => ['nullable', 'integer', 'exists:operatories,id'],
            'start_at'            => ['required', 'date'],
            'end_at'              => ['required', 'date', 'after:start_at',
                                       'before_or_equal:'.now()->parse($this->input('start_at'))->endOfDay()->toDateTimeString()],
            'status'              => ['sometimes', 'string', Rule::in(Appointment::STATUSES)],
            'chief_complaint'     => ['nullable', 'string', 'max:500'],
            'notes'               => ['nullable', 'string'],
            'color'               => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
