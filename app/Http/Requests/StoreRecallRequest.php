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
            'due_date'   => ['required', 'date',
                              Rule::unique('recalls', 'due_date')->where('patient_id', $this->input('patient_id'))],
            'status'     => ['sometimes', 'string', Rule::in(Recall::STATUSES)],
            'notes'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.unique' => 'A recall for this patient on that date already exists.',
        ];
    }
}
