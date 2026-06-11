<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'appointment_id' => ['sometimes', 'nullable', 'integer', 'exists:appointments,id'],
            'provider_id'    => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'issued_at'      => ['sometimes', 'required', 'date'],
            'due_at'         => ['sometimes', 'nullable', 'date', 'after_or_equal:issued_at'],
            'discount'       => ['sometimes', 'numeric', 'min:0'],
            'tax'            => ['sometimes', 'numeric', 'min:0'],
            'notes'          => ['sometimes', 'nullable', 'string'],
        ];
    }
}
