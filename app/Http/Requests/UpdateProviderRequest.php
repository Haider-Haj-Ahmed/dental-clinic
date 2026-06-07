<?php

namespace App\Http\Requests;

use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        /** @var Provider $provider */
        $provider = $this->route('provider');

        return [
            'user_id' => [
                'sometimes', 'nullable', 'integer', 'exists:users,id',
                Rule::unique('providers', 'user_id')->ignore($provider->id),
            ],
            'operatory_id'   => ['sometimes', 'nullable', 'integer', 'exists:operatories,id'],
            'name'           => ['sometimes', 'required', 'string', 'max:255'],
            'specialty'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'          => ['sometimes', 'nullable', 'email', 'max:255'],
            'license_number' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('providers', 'license_number')->ignore($provider->id),
            ],
            'bio'       => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
