<?php

namespace App\Http\Requests;

use App\Models\Provider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Provider $provider */
        $provider = $this->route('provider');

        return [
            'user_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('providers', 'user_id')->ignore($provider->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'license_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('providers', 'license_number')->ignore($provider->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
