<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * WorkingHour
 *
 * One row per day of week (0=Sunday … 6=Saturday).
 * Seeded with 7 rows on fresh install.
 * Used by appointment booking to reject slots outside working hours.
 */
class WorkingHour extends Model
{
    protected $fillable = [
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed'   => 'boolean',
        ];
    }

    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Unknown';
    }
}
