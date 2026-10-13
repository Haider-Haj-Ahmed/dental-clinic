<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Patient portal invoice endpoints.
 * GET /api/v1/portal/invoices         — list patient's invoices
 * GET /api/v1/portal/invoices/{id}/pdf — download invoice PDF
 */
class PortalInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user();

        $invoices = Invoice::query()
            ->with(['items', 'payments'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['finalized'])
            ->orderBy('issued_at', 'desc')
            ->paginate($this->perPage($request, 10))
            ->withQueryString();

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'id'          => $inv->id,
                'number'      => ClinicSetting::instance()->invoice_prefix . str_pad($inv->id, 5, '0', STR_PAD_LEFT),
                'issued_at'   => $inv->issued_at?->toDateString(),
                'status'      => $inv->status,
                'total'       => $inv->total,
                'amount_paid' => $inv->payments->sum('amount'),
                'balance_due' => $inv->total - $inv->payments->sum('amount'),
                'currency'    => ClinicSetting::instance()->currency_symbol,
            ]),
            'total'        => $invoices->total(),
            'current_page' => $invoices->currentPage(),
            'last_page'    => $invoices->lastPage(),
        ]);
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        $patient = $request->user();

        // Patients can only download their own invoices
        abort_unless($invoice->patient_id === $patient->id, 403);
        abort_unless($invoice->status === 'finalized', 404);

        $invoice->load(['patient', 'provider', 'items', 'payments']);
        $settings   = ClinicSetting::instance();
        $amountPaid = $invoice->payments->sum('amount');

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'settings', 'amountPaid'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);

        $filename = strtolower($settings->invoice_prefix) . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->stream($filename);
    }
}
