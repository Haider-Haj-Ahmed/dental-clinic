<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'movement_type' => ['required', 'string', Rule::in(StockMovement::TYPES)],
            'quantity'      => ['required', 'integer', 'not_in:0'],
            'reason'        => ['nullable', 'string', 'max:255'],
            'reference'     => ['nullable', 'string', 'max:255'],
            'performed_at'  => ['sometimes', 'date'],
        ];
    }
}
