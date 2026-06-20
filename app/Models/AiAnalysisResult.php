<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiAnalysisResult extends Model
{
    use HasFactory;

    public const TYPE_XRAY_ANALYSIS         = 'xray_analysis';
    public const TYPE_SOAP_SUGGESTION       = 'soap_suggestion';
    public const TYPE_PRESCRIPTION_SUGGESTION = 'prescription_suggestion';
    public const TYPE_PERIO_RISK            = 'perio_risk';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_DISMISSED,
    ];

    protected $fillable = [
        'patient_id', 'requested_by',
        'source_type', 'source_id',
        'analysis_type', 'ai_provider', 'ai_model',
        'input_summary', 'result',
        'status', 'reviewed_by', 'reviewed_at', 'reviewer_notes',
    ];

    protected function casts(): array
    {
        return [
            'result'      => 'array',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewedBy(): BelongsTo  { return $this->belongsTo(User::class, 'reviewed_by'); }
}
