<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'       => ['sometimes', 'required', 'string', 'max:255'],
            'procedure_code_id' => ['sometimes', 'nullable', 'integer', 'exists:procedure_codes,id'],
            'tooth_number'      => ['sometimes', 'nullable', 'string', 'max:10'],
            'qty'               => ['sometimes', 'required', 'integer', 'min:1'],
            'unit_price'        => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
