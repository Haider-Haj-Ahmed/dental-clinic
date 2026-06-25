<?php

namespace App\Http\Requests;

use App\Models\OdontogramEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOdontogramEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
            'tooth_number' => ['required', 'integer', 'min:11', 'max:85'],
            'surface'      => ['nullable', 'string', 'max:10'],
            'entry_type'   => ['required', 'string', Rule::in([OdontogramEntry::ENTRY_TYPE_CONDITION, OdontogramEntry::ENTRY_TYPE_PROCEDURE])],
            'code'         => ['nullable', 'string', 'max:20'],
            'color_hex'    => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notes'        => ['nullable', 'string'],
            'recorded_at'  => ['sometimes', 'date'],
        ];
    }
}
