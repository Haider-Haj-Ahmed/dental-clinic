<?php

namespace App\Http\Requests;

use App\Models\Webhook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    public function rules(): array
    {
        return [
            'url'         => ['required', 'url', 'max:500'],
            'events'      => ['required', 'array', 'min:1'],
            'events.*'    => ['required', 'string', Rule::in(Webhook::EVENTS)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'events.*.in' => 'Invalid event. Allowed: ' . implode(', ', Webhook::EVENTS),
        ];
    }
}
