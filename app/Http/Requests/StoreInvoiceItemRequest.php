<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'       => ['required', 'string', 'max:255'],
            'procedure_code_id' => ['nullable', 'integer', 'exists:procedure_codes,id'],
            'tooth_number'      => ['nullable', 'string', 'max:10'],
            'qty'               => ['required', 'integer', 'min:1'],
            'unit_price'        => ['required', 'numeric', 'min:0'],
        ];
    }
}
