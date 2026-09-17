<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today', 'unique:clinic_closures,date'],
            'reason'     => ['nullable', 'string', 'max:255'],
            'all_day'    => ['sometimes', 'boolean'],
            'start_time' => ['required_if:all_day,false', 'nullable', 'date_format:H:i'],
            'end_time'   => ['required_if:all_day,false', 'nullable', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique'           => 'A closure already exists for this date.',
            'date.after_or_equal'   => 'Closure date must be today or in the future.',
            'end_time.after'        => 'End time must be after start time.',
        ];
    }
}
