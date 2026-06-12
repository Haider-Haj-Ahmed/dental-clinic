<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'   => ['sometimes', 'nullable', 'integer', 'exists:suppliers,id'],
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'sku'           => ['sometimes', 'nullable', 'string', 'max:100',
                                Rule::unique('inventory_items', 'sku')->ignore($this->route('inventory_item'))],
            'category'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'unit'          => ['sometimes', 'string', 'max:50'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'unit_cost'     => ['sometimes', 'numeric', 'min:0'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}
