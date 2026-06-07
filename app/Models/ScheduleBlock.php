<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'operatory_id',
        'start_at',
        'end_at',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'immutable_datetime',
            'end_at'   => 'immutable_datetime',
        ];
    }

    public function provider(): BelongsTo  { return $this->belongsTo(Provider::class); }
    public function operatory(): BelongsTo { return $this->belongsTo(Operatory::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
}
