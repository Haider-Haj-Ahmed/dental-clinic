<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanItem extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'treatment_plan_id', 'procedure_code_id', 'tooth_number',
        'surface', 'description', 'fee', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2'];
    }

    public function treatmentPlan(): BelongsTo  { return $this->belongsTo(TreatmentPlan::class); }
    public function procedureCode(): BelongsTo  { return $this->belongsTo(ProcedureCode::class); }
}
