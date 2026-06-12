<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_id'              => ['required', 'integer', 'exists:suppliers,id'],
            'ordered_at'               => ['nullable', 'date'],
            'expected_at'              => ['nullable', 'date'],
            'notes'                    => ['nullable', 'string'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id'=> ['required', 'integer', 'exists:inventory_items,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'        => ['required', 'numeric', 'min:0'],
        ];
    }
}
