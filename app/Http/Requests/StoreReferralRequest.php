<?php

namespace App\Http\Requests;

use App\Models\Referral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'            => ['required', 'integer', 'exists:patients,id'],
            'referring_provider_id' => ['required', 'integer', 'exists:providers,id'],
            'encounter_id'          => ['nullable', 'integer', 'exists:encounters,id'],
            'referred_to_name'      => ['required', 'string', 'max:255'],
            'referred_to_specialty' => ['nullable', 'string', 'max:255'],
            'referred_to_clinic'    => ['nullable', 'string', 'max:255'],
            'referred_to_email'     => ['nullable', 'email', 'max:255'],
            'referred_to_phone'     => ['nullable', 'string', 'max:30'],
            'reason'                => ['required', 'string'],
            'clinical_notes'        => ['nullable', 'string'],
            'relevant_history'      => ['nullable', 'string'],
            'requested_action'      => ['nullable', 'string'],
            'urgency'               => ['sometimes', 'string', Rule::in([Referral::URGENCY_ROUTINE, Referral::URGENCY_URGENT, Referral::URGENCY_EMERGENCY])],
            'referral_date'         => ['required', 'date'],
            'appointment_date'      => ['nullable', 'date', 'after_or_equal:referral_date'],
        ];
    }
}
