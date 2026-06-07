<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleBlockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider_id'  => ['nullable', 'integer', 'exists:providers,id'],
            'operatory_id' => ['nullable', 'integer', 'exists:operatories,id'],
            'start_at'     => ['required', 'date'],
            'end_at'       => ['required', 'date', 'after:start_at'],
            'reason'       => ['nullable', 'string', 'max:255'],
        ];
    }
}
