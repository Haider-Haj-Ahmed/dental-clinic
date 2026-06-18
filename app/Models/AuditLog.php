<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null; // single timestamp only

    public const ACTION_CREATED  = 'created';
    public const ACTION_UPDATED  = 'updated';
    public const ACTION_DELETED  = 'deleted';
    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
