<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerioMeasureRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tooth_number'      => ['required', 'integer', 'min:11', 'max:85'],
            'site'              => ['required', 'string', 'in:MB,B,DB,ML,L,DL'],
            'probing_depth'     => ['nullable', 'integer', 'min:0', 'max:20'],
            'recession'         => ['nullable', 'integer', 'min:-5', 'max:15'],
            'bleeding_on_probe' => ['sometimes', 'boolean'],
            'furcation'         => ['sometimes', 'integer', 'min:0', 'max:3'],
            'mobility'          => ['sometimes', 'integer', 'min:0', 'max:3'],
            'suppuration'       => ['sometimes', 'boolean'],
        ];
    }
}
