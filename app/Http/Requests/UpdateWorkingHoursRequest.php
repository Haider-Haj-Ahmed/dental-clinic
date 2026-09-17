<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    public function rules(): array
    {
        return [
            'hours'                  => ['required', 'array', 'min:1', 'max:7'],
            'hours.*.day_of_week'    => ['required', 'integer', 'between:0,6'],
            'hours.*.is_closed'      => ['required', 'boolean'],
            'hours.*.open_time'      => ['required_if:hours.*.is_closed,false', 'nullable', 'date_format:H:i'],
            'hours.*.close_time'     => ['required_if:hours.*.is_closed,false', 'nullable', 'date_format:H:i', 'after:hours.*.open_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'hours.*.close_time.after' => 'Close time must be after open time.',
            'hours.*.open_time.required_if'  => 'Open time is required when the day is not closed.',
            'hours.*.close_time.required_if' => 'Close time is required when the day is not closed.',
        ];
    }
}
