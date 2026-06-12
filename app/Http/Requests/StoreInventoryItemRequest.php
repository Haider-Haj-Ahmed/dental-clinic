<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'   => ['nullable', 'integer', 'exists:suppliers,id'],
            'name'          => ['required', 'string', 'max:255'],
            'sku'           => ['nullable', 'string', 'max:100', 'unique:inventory_items,sku'],
            'category'      => ['nullable', 'string', 'max:100'],
            'unit'          => ['sometimes', 'string', 'max:50'],
            'current_stock' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'unit_cost'     => ['sometimes', 'numeric', 'min:0'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}
