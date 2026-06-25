<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerioExamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'  => ['required', 'integer', 'exists:patients,id'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'exam_date'   => ['required', 'date', 'before_or_equal:today'],
            'notes'       => ['nullable', 'string'],
            'measures'    => ['sometimes', 'array'],
            'measures.*.tooth_number'      => ['required', 'integer', 'min:11', 'max:85'],
            'measures.*.site'              => ['required', 'string', 'in:MB,B,DB,ML,L,DL'],
            'measures.*.probing_depth'     => ['nullable', 'integer', 'min:0', 'max:20'],
            'measures.*.recession'         => ['nullable', 'integer', 'min:-5', 'max:15'],
            'measures.*.bleeding_on_probe' => ['sometimes', 'boolean'],
            'measures.*.furcation'         => ['sometimes', 'integer', 'min:0', 'max:3'],
            'measures.*.mobility'          => ['sometimes', 'integer', 'min:0', 'max:3'],
            'measures.*.suppuration'       => ['sometimes', 'boolean'],
        ];
    }
}
