<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleBlockRequest;
use App\Http\Requests\UpdateScheduleBlockRequest;
use App\Http\Resources\ScheduleBlockResource;
use App\Models\ScheduleBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ScheduleBlockController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ScheduleBlock::class, 'schedule_block');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $blocks = ScheduleBlock::query()
            ->with(['provider', 'operatory', 'creator'])
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('operatory_id'), fn ($q) => $q->where('operatory_id', $request->integer('operatory_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('start_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('start_at', '<=', $request->date('date_to')))
            ->orderBy('start_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ScheduleBlockResource::collection($blocks);
    }

    public function store(StoreScheduleBlockRequest $request): ScheduleBlockResource
    {
        $payload = $request->validated();
        $payload['created_by'] = $request->user()->id;

        $block = ScheduleBlock::query()->create($payload);

        return ScheduleBlockResource::make($block->load(['provider', 'operatory', 'creator']));
    }

    public function show(ScheduleBlock $scheduleBlock): ScheduleBlockResource
    {
        return ScheduleBlockResource::make($scheduleBlock->load(['provider', 'operatory', 'creator']));
    }

    public function update(UpdateScheduleBlockRequest $request, ScheduleBlock $scheduleBlock): ScheduleBlockResource
    {
        $scheduleBlock->update($request->validated());

        return ScheduleBlockResource::make($scheduleBlock->refresh()->load(['provider', 'operatory', 'creator']));
    }

    public function destroy(ScheduleBlock $scheduleBlock): Response
    {
        $scheduleBlock->delete();

        return $this->noContentResponse();
    }
}
