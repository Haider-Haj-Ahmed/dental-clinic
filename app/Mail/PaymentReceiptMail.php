<?php

namespace App\Mail;

use App\Models\ClinicSetting;
use App\Models\Payment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReceiptMail extends BaseMail
{
    private ClinicSetting $settings;

    public function __construct(private readonly Payment $payment)
    {
        $this->settings = ClinicSetting::instance();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt — ' . $this->settings->clinic_name,
        );
    }

    public function content(): Content
    {
        $patient = $this->payment->patient;
        return new Content(
            view: 'emails.billing.payment-receipt',
            with: [
                'patientName'  => $patient->first_name . ' ' . $patient->last_name,
                'payment'      => $this->payment,
                'settings'     => $this->settings,
                'branding'     => $this->branding(),
            ],
        );
    }
}
