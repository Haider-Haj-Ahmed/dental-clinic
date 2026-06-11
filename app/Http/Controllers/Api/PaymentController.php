<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->with(['paymentMethod', 'recordedBy'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('invoice_id'), fn ($q) => $q->where('invoice_id', $request->integer('invoice_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date('date_to')))
            ->orderBy('paid_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PaymentResource::collection($payments);
    }

    public function store(StorePaymentRequest $request): PaymentResource
    {
        $payment = DB::transaction(function () use ($request) {
            $invoice = Invoice::query()->findOrFail($request->integer('invoice_id'));

            abort_if(
                in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID]),
                422,
                'Cannot record payment on a draft or voided invoice.'
            );

            $payment = Payment::query()->create([
                ...$request->validated(),
                'patient_id'  => $invoice->patient_id,
                'recorded_by' => $request->user()->id,
            ]);

            // Update invoice status based on amount paid
            $totalPaid = $invoice->payments()->sum('amount');
            $newStatus = match (true) {
                $totalPaid >= $invoice->total => Invoice::STATUS_PAID,
                $totalPaid > 0               => Invoice::STATUS_PARTIAL,
                default                      => $invoice->status,
            };

            $invoice->update(['status' => $newStatus]);

            return $payment;
        });

        return PaymentResource::make($payment->load(['paymentMethod', 'recordedBy']));
    }

    public function show(Payment $payment): PaymentResource
    {
        return PaymentResource::make($payment->load(['paymentMethod', 'recordedBy']));
    }

    public function destroy(Payment $payment): Response
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();

            // Recalculate invoice status
            $totalPaid = $invoice->payments()->sum('amount');
            $newStatus = match (true) {
                $totalPaid <= 0              => Invoice::STATUS_FINALIZED,
                $totalPaid >= $invoice->total => Invoice::STATUS_PAID,
                default                      => Invoice::STATUS_PARTIAL,
            };

            $invoice->update(['status' => $newStatus]);
        });

        return response()->noContent();
    }
}
