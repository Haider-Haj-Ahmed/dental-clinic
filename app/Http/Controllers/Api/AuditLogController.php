<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        $request->validate([
            'model'     => ['sometimes', 'string'],
            'action'    => ['sometimes', 'string', 'in:created,updated,deleted,restored'],
            'user_id'   => ['sometimes', 'integer', 'exists:users,id'],
            'model_id'  => ['sometimes', 'integer'],
            'date_from' => ['sometimes', 'date'],
            'date_to'   => ['sometimes', 'date', 'after_or_equal:date_from'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('model'), function ($q) use ($request) {
                // Accept short name (e.g. "Patient") or full class name
                $model = $request->string('model');
                $q->where(fn ($sub) => $sub
                    ->where('model_type', $model)
                    ->orWhere('model_type', 'like', "%\\{$model}")
                );
            })
            ->when($request->filled('action'),   fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('user_id'),  fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('model_id'), fn ($q) => $q->where('model_id', $request->integer('model_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'),   fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return AuditLogResource::collection($logs);
    }

    public function show(AuditLog $auditLog): AuditLogResource
    {
        $this->authorize('view', $auditLog);

        return AuditLogResource::make($auditLog->load('user'));
    }
}
