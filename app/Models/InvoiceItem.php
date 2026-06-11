<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'procedure_code_id',
        'description', 'tooth_number',
        'qty', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'unit_price' => 'decimal:2',
            'total'      => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo       { return $this->belongsTo(Invoice::class); }
    public function procedureCode(): BelongsTo { return $this->belongsTo(ProcedureCode::class); }
}
