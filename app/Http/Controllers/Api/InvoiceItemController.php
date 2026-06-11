<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceItemRequest;
use App\Http\Requests\UpdateInvoiceItemRequest;
use App\Http\Resources\InvoiceItemResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvoiceItemController extends Controller
{
    public function index(Invoice $invoice): AnonymousResourceCollection
    {
        $this->authorize('view', $invoice);

        return InvoiceItemResource::collection(
            $invoice->items()->with('procedureCode')->get()
        );
    }

    public function store(StoreInvoiceItemRequest $request, Invoice $invoice): InvoiceItemResource
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== Invoice::STATUS_DRAFT, 422, 'Items can only be added to draft invoices.');

        $data          = $request->validated();
        $data['total'] = $data['qty'] * $data['unit_price'];

        $item = $invoice->items()->create($data);
        $invoice->recalculateTotals();

        return InvoiceItemResource::make($item->load('procedureCode'));
    }

    public function update(UpdateInvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item): InvoiceItemResource
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== Invoice::STATUS_DRAFT, 422, 'Items can only be edited on draft invoices.');
        abort_if($item->invoice_id !== $invoice->id, 404);

        $data = $request->validated();
        $qty        = $data['qty']        ?? $item->qty;
        $unit_price = $data['unit_price'] ?? $item->unit_price;
        $data['total'] = $qty * $unit_price;

        $item->update($data);
        $invoice->recalculateTotals();

        return InvoiceItemResource::make($item->refresh()->load('procedureCode'));
    }

    public function destroy(Invoice $invoice, InvoiceItem $item): Response
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== Invoice::STATUS_DRAFT, 422, 'Items can only be removed from draft invoices.');
        abort_if($item->invoice_id !== $invoice->id, 404);

        $item->delete();
        $invoice->recalculateTotals();

        return response()->noContent();
    }
}
