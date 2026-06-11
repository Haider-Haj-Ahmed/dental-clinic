<?php

namespace App\Http\Requests;

use App\Models\Recall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecallRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'due_date' => ['sometimes', 'required', 'date'],
            'status'   => ['sometimes', 'required', 'string', Rule::in(Recall::STATUSES)],
            'notes'    => ['sometimes', 'nullable', 'string'],
        ];
    }
}
