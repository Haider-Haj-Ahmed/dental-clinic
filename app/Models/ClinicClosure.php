<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClinicClosure
 *
 * Represents a holiday or one-off clinic closure.
 * Unique per date — one closure entry per calendar day.
 * Used by appointment booking to block slots on closure dates.
 */
class ClinicClosure extends Model
{
    protected $fillable = [
        'date',
        'reason',
        'all_day',
        'start_time',
        'end_time',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'    => 'date',
            'all_day' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
