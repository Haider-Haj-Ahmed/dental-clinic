<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Fields that should never appear in audit logs (sensitive or noisy).
     */
    private const EXCLUDED_FIELDS = [
        'password', 'remember_token', 'updated_at',
    ];

    public function log(
        string $action,
        Model $model,
        array $oldValues = [],
        array $newValues = [],
    ): void {
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'model_type' => get_class($model),
            'model_id'   => $model->getKey(),
            'old_values' => $this->filter($oldValues),
            'new_values' => $this->filter($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    private function filter(array $values): ?array
    {
        if (empty($values)) {
            return null;
        }

        return array_diff_key($values, array_flip(self::EXCLUDED_FIELDS));
    }
}
