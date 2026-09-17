<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ClinicSetting;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = Invoice::query()
            ->with(['patient', 'provider'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issued_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issued_at', '<=', $request->date('date_to')))
            ->orderBy('issued_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request): InvoiceResource
    {
        $invoice = DB::transaction(function () use ($request) {
            $data    = $request->safe()->except('items');
            $invoice = Invoice::query()->create($data);

            foreach ($request->input('items') as $item) {
                $item['total'] = $item['qty'] * $item['unit_price'];
                $invoice->items()->create($item);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        return InvoiceResource::make($invoice->load(['patient', 'provider', 'items.procedureCode']));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return InvoiceResource::make(
            $invoice->load(['patient', 'provider', 'items.procedureCode', 'payments.paymentMethod'])
        );
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        abort_if($invoice->status === Invoice::STATUS_VOID, 422, 'Cannot edit a voided invoice.');
        abort_if($invoice->status === Invoice::STATUS_FINALIZED, 422, 'Cannot edit a finalized invoice. Void it first.');

        $invoice->update($request->validated());
        $invoice->recalculateTotals();

        return InvoiceResource::make($invoice->refresh()->load(['patient', 'provider', 'items.procedureCode']));
    }

    public function destroy(Invoice $invoice): Response
    {
        abort_if($invoice->status !== Invoice::STATUS_DRAFT, 422, 'Only draft invoices can be deleted.');

        $invoice->delete();

        return response()->noContent();
    }

    /** POST /invoices/{invoice}/finalize */
    public function finalize(Invoice $invoice, Request $request): InvoiceResource
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== Invoice::STATUS_DRAFT, 422, 'Only draft invoices can be finalized.');
        abort_if($invoice->items()->count() === 0, 422, 'Cannot finalize an invoice with no items.');

        $invoice->update([
            'status'       => Invoice::STATUS_FINALIZED,
            'finalized_by' => $request->user()->id,
        ]);

        return InvoiceResource::make($invoice->refresh()->load(['patient', 'provider', 'items']));
    }

    /** POST /invoices/{invoice}/void */
    public function void(Invoice $invoice, Request $request): InvoiceResource
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status === Invoice::STATUS_VOID, 422, 'Invoice is already voided.');

        $invoice->update([
            'status'    => Invoice::STATUS_VOID,
            'voided_by' => $request->user()->id,
        ]);

        return InvoiceResource::make($invoice->refresh());
    }

    /** GET /patients/{patient}/ledger */
    public function ledger(Request $request, \App\Models\Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $invoices = Invoice::query()
            ->where('patient_id', $patient->id)
            ->with(['items', 'payments.paymentMethod'])
            ->orderBy('issued_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $totals = Invoice::query()
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', [Invoice::STATUS_VOID, Invoice::STATUS_DRAFT])
            ->selectRaw('SUM(total) as total_billed')
            ->first();

        $totalPaid = \App\Models\Payment::query()
            ->where('patient_id', $patient->id)
            ->sum('amount');

        return response()->json([
            'data'    => InvoiceResource::collection($invoices)->resolve(),
            'summary' => [
                'total_billed'     => (float) ($totals->total_billed ?? 0),
                'total_paid'       => (float) $totalPaid,
                'outstanding'      => (float) (($totals->total_billed ?? 0) - $totalPaid),
            ],
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'total'        => $invoices->total(),
                'per_page'     => $invoices->perPage(),
            ],
        ]);
    }

    /**
     * Generate and stream invoice as PDF.
     * GET /api/v1/invoices/{invoice}/pdf
     */
    public function pdf(Invoice $invoice): \Illuminate\Http\Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'provider', 'items', 'payments', 'finalizedBy']);

        $settings   = ClinicSetting::instance();
        $amountPaid = $invoice->payments->sum('amount');

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'settings', 'amountPaid'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'  => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
            ]);

        $filename = strtolower(str_replace(' ', '-', $settings->invoice_prefix))
                  . str_pad($invoice->id, 5, '0', STR_PAD_LEFT)
                  . '.pdf';

        return $pdf->stream($filename);
    }
}
