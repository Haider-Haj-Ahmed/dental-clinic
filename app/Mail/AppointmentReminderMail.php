<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentReminderMail extends BaseMail
{
    private ClinicSetting $settings;

    public function __construct(
        private readonly Appointment $appointment,
        private readonly string $timeframe = '24 hours',
    ) {
        $this->settings = ClinicSetting::instance();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Your appointment is in ' . $this->timeframe,
        );
    }

    public function content(): Content
    {
        $patient = $this->appointment->patient;
        return new Content(
            view: 'emails.appointments.reminder',
            with: [
                'patientName'  => $patient->first_name . ' ' . $patient->last_name,
                'appointment'  => $this->appointment,
                'timeframe'    => $this->timeframe,
                'settings'     => $this->settings,
                'branding'     => $this->branding(),
            ],
        );
    }
}
