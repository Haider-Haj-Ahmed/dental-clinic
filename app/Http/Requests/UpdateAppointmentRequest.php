<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        // Providers may only update notes on their own appointments
        if ($user->isProvider()) {
            return [
                'notes' => ['sometimes', 'nullable', 'string'],
            ];
        }

        return [
            'patient_id'          => ['sometimes', 'required', 'integer',
                                       Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'provider_id'         => ['sometimes', 'required', 'integer', 'exists:providers,id'],
            'appointment_type_id' => ['sometimes', 'nullable', 'integer', 'exists:appointment_types,id'],
            'operatory_id'        => ['sometimes', 'nullable', 'integer', 'exists:operatories,id'],
            'start_at'            => ['sometimes', 'required', 'date'],
            'end_at'              => ['sometimes', 'required', 'date', 'after:start_at'],
            'status'              => ['sometimes', 'string', Rule::in(Appointment::STATUSES)],
            'chief_complaint'     => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes'               => ['sometimes', 'nullable', 'string'],
            'color'               => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
