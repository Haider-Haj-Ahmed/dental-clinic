<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentCancelledMail extends BaseMail
{
    private ClinicSetting $settings;

    public function __construct(private readonly Appointment $appointment)
    {
        $this->settings = ClinicSetting::instance();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your appointment has been cancelled',
        );
    }

    public function content(): Content
    {
        $patient = $this->appointment->patient;
        return new Content(
            view: 'emails.appointments.cancelled',
            with: [
                'patientName'  => $patient->first_name . ' ' . $patient->last_name,
                'appointment'  => $this->appointment,
                'settings'     => $this->settings,
                'branding'     => $this->branding(),
            ],
        );
    }
}
