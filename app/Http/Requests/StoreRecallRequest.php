<?php

namespace App\Http\Requests;

use App\Models\Recall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecallRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'due_date'   => ['required', 'date'],
            'status'     => ['sometimes', 'string', Rule::in(Recall::STATUSES)],
            'notes'      => ['nullable', 'string'],
        ];
    }
}
