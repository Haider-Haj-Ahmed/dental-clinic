<?php

namespace App\Mail;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoiceFinalizedMail extends BaseMail
{
    private ClinicSetting $settings;

    public function __construct(private readonly Invoice $invoice)
    {
        $this->settings = ClinicSetting::instance();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice ' . $this->settings->invoice_prefix . str_pad($this->invoice->id, 5, '0', STR_PAD_LEFT),
        );
    }

    public function content(): Content
    {
        $patient    = $this->invoice->patient;
        $amountPaid = $this->invoice->payments->sum('amount');

        return new Content(
            view: 'emails.billing.invoice-finalized',
            with: [
                'patientName'  => $patient->first_name . ' ' . $patient->last_name,
                'invoice'      => $this->invoice,
                'amountPaid'   => $amountPaid,
                'settings'     => $this->settings,
                'branding'     => $this->branding(),
            ],
        );
    }

    public function attachments(): array
    {
        $invoice    = $this->invoice;
        $settings   = $this->settings;
        $amountPaid = $invoice->payments->sum('amount');

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'settings', 'amountPaid'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);

        $filename = strtolower($settings->invoice_prefix) . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) . '.pdf';

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
