<?php

namespace App\Http\Requests;

use App\Models\OdontogramEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOdontogramEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tooth_number' => ['sometimes', 'required', 'integer', 'min:11', 'max:85'],
            'surface'      => ['sometimes', 'nullable', 'string', 'max:10'],
            'entry_type'   => ['sometimes', 'required', 'string', Rule::in([OdontogramEntry::ENTRY_TYPE_CONDITION, OdontogramEntry::ENTRY_TYPE_PROCEDURE])],
            'code'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'color_hex'    => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notes'        => ['sometimes', 'nullable', 'string'],
            'recorded_at'  => ['sometimes', 'date'],
        ];
    }
}
