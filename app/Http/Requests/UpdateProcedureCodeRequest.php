<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcedureCodeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'        => ['sometimes', 'required', 'string', 'max:20',
                              Rule::unique('procedure_codes', 'code')->ignore($this->route('procedure_code'))],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'fee_default' => ['sometimes', 'numeric', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
