<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureCode extends Model
{
    protected $fillable = ['code', 'description', 'fee_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'fee_default' => 'decimal:2',
            'is_active'   => 'boolean',
        ];
    }
}
