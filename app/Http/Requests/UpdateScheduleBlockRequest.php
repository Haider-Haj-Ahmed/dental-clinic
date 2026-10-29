<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateScheduleBlockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider_id'  => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'operatory_id' => ['sometimes', 'nullable', 'integer', 'exists:operatories,id'],
            'start_at'     => ['sometimes', 'required', 'date'],
            'end_at'       => ['sometimes', 'required', 'date',
                                  'after:' . ($this->input('start_at')
                                      ? 'start_at'
                                      : Carbon::parse($this->route('schedule_block')->start_at)->toDateTimeString())],
            'reason'       => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
