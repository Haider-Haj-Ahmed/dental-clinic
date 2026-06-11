<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'     => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'provider_id'    => ['nullable', 'integer', 'exists:providers,id'],
            'issued_at'      => ['required', 'date'],
            'due_at'         => ['nullable', 'date', 'after_or_equal:issued_at'],
            'discount'       => ['sometimes', 'numeric', 'min:0'],
            'tax'            => ['sometimes', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.description'      => ['required', 'string', 'max:255'],
            'items.*.procedure_code_id'=> ['nullable', 'integer', 'exists:procedure_codes,id'],
            'items.*.tooth_number'     => ['nullable', 'string', 'max:10'],
            'items.*.qty'              => ['required', 'integer', 'min:1'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
        ];
    }
}
