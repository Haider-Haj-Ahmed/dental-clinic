<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function __construct(private readonly AuditService $auditService) {}

    public function created(Model $model): void
    {
        $this->auditService->log(
            AuditLog::ACTION_CREATED,
            $model,
            [],
            $model->getAttributes(),
        );
    }

    public function updated(Model $model): void
    {
        // Only log if something actually changed
        if (empty($model->getDirty())) {
            return;
        }

        $this->auditService->log(
            AuditLog::ACTION_UPDATED,
            $model,
            array_intersect_key($model->getOriginal(), $model->getDirty()),
            $model->getDirty(),
        );
    }

    public function deleted(Model $model): void
    {
        $this->auditService->log(
            AuditLog::ACTION_DELETED,
            $model,
            $model->getAttributes(),
            [],
        );
    }

    public function restored(Model $model): void
    {
        $this->auditService->log(
            AuditLog::ACTION_RESTORED,
            $model,
            [],
            $model->getAttributes(),
        );
    }
}
