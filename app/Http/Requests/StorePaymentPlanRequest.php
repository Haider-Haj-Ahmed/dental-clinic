<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'   => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'invoice_id'   => ['nullable', 'integer', Rule::exists('invoices', 'id')->where('patient_id', $this->input('patient_id'))],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'installments' => ['required', 'integer', 'min:2', 'max:60'],
            'start_date'   => ['required', 'date'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
