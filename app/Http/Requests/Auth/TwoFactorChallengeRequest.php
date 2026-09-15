<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Either a 6-digit TOTP code or an 8-character recovery code
            'two_factor_token' => ['required', 'string'],
            'code'             => ['required_without:recovery_code', 'nullable', 'string', 'digits:6'],
            'recovery_code'    => ['required_without:code', 'nullable', 'string'],
            'device_name'      => ['required', 'string', 'max:255'],
        ];
    }
}
