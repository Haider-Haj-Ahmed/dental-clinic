<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerioMeasureRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tooth_number'      => ['required', 'integer', 'min:11', 'max:85'],
            'site'              => ['required', 'string', 'in:MB,B,DB,ML,L,DL',
                                     Rule::unique('perio_measures', 'site')
                                         ->where('perio_exam_id', $this->route('perioExam')->id)
                                         ->where('tooth_number', $this->input('tooth_number'))],
            'probing_depth'     => ['nullable', 'integer', 'min:0', 'max:20'],
            'recession'         => ['nullable', 'integer', 'min:-5', 'max:15'],
            'bleeding_on_probe' => ['sometimes', 'boolean'],
            'furcation'         => ['sometimes', 'integer', 'min:0', 'max:3'],
            'mobility'          => ['sometimes', 'integer', 'min:0', 'max:3'],
            'suppuration'       => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'site.unique' => 'A measure for this tooth and site already exists in this exam.',
        ];
    }
}
