<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecallRequest;
use App\Http\Requests\UpdateRecallRequest;
use App\Http\Resources\RecallResource;
use App\Models\Recall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecallController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Recall::class, 'recall');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $recalls = Recall::query()
            ->with('patient')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('due_before'), fn ($q) => $q->whereDate('due_date', '<=', $request->date('due_before')))
            ->when($request->filled('due_after'), fn ($q) => $q->whereDate('due_date', '>=', $request->date('due_after')))
            ->orderBy('due_date')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return RecallResource::collection($recalls);
    }

    /** GET /recalls/due — recalls due on or before today */
    public function due(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Recall::class);

        $recalls = Recall::query()
            ->with('patient')
            ->whereIn('status', [Recall::STATUS_PENDING, Recall::STATUS_SENT])
            ->whereDate('due_date', '<=', now())
            ->orderBy('due_date')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return RecallResource::collection($recalls);
    }

    public function store(StoreRecallRequest $request): RecallResource
    {
        $recall = Recall::query()->create($request->validated());

        return RecallResource::make($recall->load('patient'));
    }

    public function show(Recall $recall): RecallResource
    {
        return RecallResource::make($recall->load('patient'));
    }

    public function update(UpdateRecallRequest $request, Recall $recall): RecallResource
    {
        $recall->update($request->validated());

        return RecallResource::make($recall->refresh()->load('patient'));
    }

    /** PATCH /recalls/{recall}/status */
    public function updateStatus(Request $request, Recall $recall): RecallResource
    {
        $this->authorize('update', $recall);

        $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(Recall::STATUSES)],
        ]);

        $recall->update(['status' => $request->string('status')]);

        return RecallResource::make($recall->refresh()->load('patient'));
    }

    /** POST /recalls/{recall}/send-reminder */
    public function sendReminder(Recall $recall): JsonResponse
    {
        $this->authorize('update', $recall);

        // Log the reminder — actual sending handled by notification queue in Phase 4
        $recall->communicationLogs()->create([
            'patient_id'   => $recall->patient_id,
            'channel'      => 'sms',
            'direction'    => 'out',
            'subject'      => 'Recall Reminder',
            'body_preview' => "Reminder: your recall appointment is due on {$recall->due_date->toDateString()}.",
            'status'       => 'queued',
        ]);

        $recall->update([
            'status'                => Recall::STATUS_SENT,
            'last_reminder_sent_at' => now(),
        ]);

        return $this->successResponse(message: 'Reminder queued successfully.');
    }

    public function destroy(Recall $recall): Response
    {
        $recall->delete();

        return $this->noContentResponse();
    }
}
