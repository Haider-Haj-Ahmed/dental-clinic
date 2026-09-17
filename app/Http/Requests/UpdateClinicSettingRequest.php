<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    public function rules(): array
    {
        return [
            // Identity
            'clinic_name'    => ['sometimes', 'string', 'max:255'],
            'clinic_email'   => ['sometimes', 'nullable', 'email', 'max:255'],
            'clinic_phone'   => ['sometimes', 'nullable', 'string', 'max:30'],
            'clinic_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'website'        => ['sometimes', 'nullable', 'url', 'max:255'],

            // Locale
            'timezone'        => ['sometimes', 'string', 'timezone'],
            'currency_code'   => ['sometimes', 'string', 'size:3'],
            'currency_symbol' => ['sometimes', 'string', 'max:10'],
            'date_format'     => ['sometimes', 'string', 'max:20'],
            'time_format'     => ['sometimes', 'string', 'max:10'],
            'language'        => ['sometimes', 'string', Rule::in(['en', 'ar'])],

            // Branding
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // Billing
            'tax_name'               => ['sometimes', 'string', 'max:50'],
            'tax_rate'               => ['sometimes', 'integer', 'min:0', 'max:100'],
            'invoice_prefix'         => ['sometimes', 'string', 'max:10'],
            'invoice_starting_number'=> ['sometimes', 'integer', 'min:1'],

            // Appointments
            'appointment_slot_minutes'  => ['sometimes', 'integer', Rule::in([10, 15, 20, 30, 45, 60])],
            'cancellation_policy_hours' => ['sometimes', 'integer', 'min:0', 'max:168'],

            // Reminders
            'reminder_first_hours'      => ['sometimes', 'integer', 'min:1', 'max:72'],
            'reminder_second_hours'     => ['sometimes', 'integer', 'min:1', 'max:24'],
            'reminder_sms_enabled'      => ['sometimes', 'boolean'],
            'reminder_email_enabled'    => ['sometimes', 'boolean'],
            'reminder_whatsapp_enabled' => ['sometimes', 'boolean'],

            // Security
            'require_2fa'              => ['sometimes', 'boolean'],
            'session_timeout_minutes'  => ['sometimes', 'integer', 'min:15', 'max:1440'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.regex'   => 'Primary color must be a valid hex color (e.g. #4fdbcc).',
            'timezone.timezone'     => 'The timezone must be a valid PHP timezone identifier.',
            'currency_code.size'    => 'Currency code must be exactly 3 characters (e.g. USD, SYP).',
            'appointment_slot_minutes.in' => 'Appointment slot must be one of: 10, 15, 20, 30, 45, or 60 minutes.',
        ];
    }
}
