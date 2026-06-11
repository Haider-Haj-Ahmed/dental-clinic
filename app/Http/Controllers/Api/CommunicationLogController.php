<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommunicationLogResource;
use App\Models\CommunicationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommunicationLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CommunicationLog::class);

        $logs = CommunicationLog::query()
            ->with(['patient', 'provider'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('recall_id'), fn ($q) => $q->where('recall_id', $request->integer('recall_id')))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return CommunicationLogResource::collection($logs);
    }

    public function show(CommunicationLog $communicationLog): CommunicationLogResource
    {
        $this->authorize('view', $communicationLog);

        return CommunicationLogResource::make($communicationLog->load(['patient', 'provider']));
    }
}
