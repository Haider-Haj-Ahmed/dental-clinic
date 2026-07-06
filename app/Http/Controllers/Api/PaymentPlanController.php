<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentPlanRequest;
use App\Http\Resources\PaymentPlanResource;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PaymentPlanController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PaymentPlan::class, 'payment_plan');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = PaymentPlan::query()
            ->with(['patient', 'invoice'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('start_date', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PaymentPlanResource::collection($plans);
    }

    public function store(StorePaymentPlanRequest $request): PaymentPlanResource
    {
        $plan = DB::transaction(function () use ($request) {
            $plan = PaymentPlan::query()->create($request->validated());

            // Auto-generate installment items
            $installmentAmount = round($plan->total_amount / $plan->installments, 2);
            $startDate         = $plan->start_date;

            for ($i = 0; $i < $plan->installments; $i++) {
                $plan->items()->create([
                    'due_date' => $startDate->addMonths($i)->toDateString(),
                    'amount'   => $i === $plan->installments - 1
                        // Last installment absorbs rounding difference
                        ? round($plan->total_amount - ($installmentAmount * ($plan->installments - 1)), 2)
                        : $installmentAmount,
                ]);
            }

            return $plan;
        });

        return PaymentPlanResource::make($plan->load(['patient', 'invoice', 'items']));
    }

    public function show(PaymentPlan $paymentPlan): PaymentPlanResource
    {
        return PaymentPlanResource::make($paymentPlan->load(['patient', 'invoice', 'items']));
    }

    public function destroy(PaymentPlan $paymentPlan): Response
    {
        abort_if($paymentPlan->status === PaymentPlan::STATUS_COMPLETED, 422, 'Cannot delete a completed payment plan.');

        $paymentPlan->update(['status' => PaymentPlan::STATUS_CANCELLED]);

        return $this->noContentResponse();
    }
}
