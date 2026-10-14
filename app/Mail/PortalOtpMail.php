<?php

namespace App\Mail;

use App\Models\Patient;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PortalOtpMail extends BaseMail
{
    public function __construct(
        private readonly Patient $patient,
        private readonly string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your login code — ' . config('app.clinic_name', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal.otp',
            with: [
                'patientName' => $this->patient->first_name,
                'otp'         => $this->otp,
                'expiresIn'   => 10,
                'branding'    => $this->branding(),
            ],
        );
    }
}
