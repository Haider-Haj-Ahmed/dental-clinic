<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcedureCodeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:20', 'unique:procedure_codes,code'],
            'description' => ['required', 'string', 'max:255'],
            'fee_default' => ['sometimes', 'numeric', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
