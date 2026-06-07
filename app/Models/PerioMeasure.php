<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerioMeasure extends Model
{
    protected $fillable = [
        'perio_exam_id', 'tooth_number', 'site',
        'probing_depth', 'recession', 'bleeding_on_probe',
        'furcation', 'mobility', 'suppuration',
    ];

    protected function casts(): array
    {
        return [
            'bleeding_on_probe' => 'boolean',
            'suppuration'       => 'boolean',
        ];
    }

    public function perioExam(): BelongsTo { return $this->belongsTo(PerioExam::class); }
}
