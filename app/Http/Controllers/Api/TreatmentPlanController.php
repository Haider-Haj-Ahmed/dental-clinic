<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTreatmentPlanRequest;
use App\Http\Requests\UpdateTreatmentPlanRequest;
use App\Http\Resources\TreatmentPlanResource;
use App\Models\TreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TreatmentPlanController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(TreatmentPlan::class, 'treatment_plan');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = TreatmentPlan::query()
            ->with(['patient', 'provider'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return TreatmentPlanResource::collection($plans);
    }

    public function store(StoreTreatmentPlanRequest $request): TreatmentPlanResource
    {
        $plan = DB::transaction(function () use ($request) {
            $plan = TreatmentPlan::query()->create([
                ...$request->safe()->except('items'),
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->input('items', []) as $i => $item) {
                $plan->items()->create([...$item, 'sort_order' => $item['sort_order'] ?? $i]);
            }

            $plan->update(['total_fee' => $plan->items()->sum('fee')]);

            return $plan;
        });

        return TreatmentPlanResource::make($plan->load(['patient', 'provider', 'items.procedureCode', 'creator']));
    }

    public function show(TreatmentPlan $treatmentPlan): TreatmentPlanResource
    {
        return TreatmentPlanResource::make(
            $treatmentPlan->load(['patient', 'provider', 'items.procedureCode', 'creator'])
        );
    }

    public function update(UpdateTreatmentPlanRequest $request, TreatmentPlan $treatmentPlan): TreatmentPlanResource
    {
        $treatmentPlan->update($request->validated());

        return TreatmentPlanResource::make($treatmentPlan->refresh()->load(['patient', 'provider', 'items']));
    }

    public function destroy(TreatmentPlan $treatmentPlan): Response
    {
        $treatmentPlan->delete();

        return response()->noContent();
    }

    /** POST /treatment-plans/{treatmentPlan}/present */
    public function present(TreatmentPlan $treatmentPlan): TreatmentPlanResource
    {
        $this->authorize('update', $treatmentPlan);
        abort_if($treatmentPlan->status !== TreatmentPlan::STATUS_DRAFT, 422, 'Only draft plans can be presented.');
        abort_if($treatmentPlan->items()->count() === 0, 422, 'Cannot present a plan with no items.');

        $treatmentPlan->update([
            'status'       => TreatmentPlan::STATUS_PRESENTED,
            'presented_at' => now(),
        ]);

        return TreatmentPlanResource::make($treatmentPlan->refresh()->load(['patient', 'provider', 'items']));
    }

    /** POST /treatment-plans/{treatmentPlan}/accept */
    public function accept(TreatmentPlan $treatmentPlan): TreatmentPlanResource
    {
        $this->authorize('update', $treatmentPlan);
        abort_if($treatmentPlan->status !== TreatmentPlan::STATUS_PRESENTED, 422, 'Only presented plans can be accepted.');

        $treatmentPlan->update([
            'status'      => TreatmentPlan::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        return TreatmentPlanResource::make($treatmentPlan->refresh()->load(['patient', 'provider', 'items']));
    }

    /** POST /treatment-plans/{treatmentPlan}/reject */
    public function reject(TreatmentPlan $treatmentPlan): TreatmentPlanResource
    {
        $this->authorize('update', $treatmentPlan);
        abort_if($treatmentPlan->status !== TreatmentPlan::STATUS_PRESENTED, 422, 'Only presented plans can be rejected.');

        $treatmentPlan->update([
            'status'      => TreatmentPlan::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);

        return TreatmentPlanResource::make($treatmentPlan->refresh()->load(['patient', 'provider', 'items']));
    }
}
