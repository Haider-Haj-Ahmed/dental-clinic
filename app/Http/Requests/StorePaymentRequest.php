<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'invoice_id'        => ['required', 'integer', 'exists:invoices,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'paid_at'           => ['required', 'date'],
            'reference'         => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string'],
        ];
    }
}
