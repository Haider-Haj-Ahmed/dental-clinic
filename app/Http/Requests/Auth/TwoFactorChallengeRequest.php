<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // The short-lived token returned by login when requires_2fa: true
            'two_factor_token' => ['required', 'string'],
            // Either a 6-digit TOTP or an 8-char recovery code
            'code'             => ['required_without:recovery_code', 'nullable', 'string', 'digits:6'],
            'recovery_code'    => ['required_without:code', 'nullable', 'string'],
            // device_name was removed: the controller uses the name cached at login time
        ];
    }
}
