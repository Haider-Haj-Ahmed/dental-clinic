<?php

namespace App\Mail;

use App\Models\ClinicSetting;
use App\Models\Recall;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RecallReminderMail extends BaseMail
{
    private ClinicSetting $settings;

    public function __construct(private readonly Recall $recall)
    {
        $this->settings = ClinicSetting::instance();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'It\'s time for your dental recall — ' . $this->settings->clinic_name,
        );
    }

    public function content(): Content
    {
        $patient = $this->recall->patient;
        return new Content(
            view: 'emails.clinical.recall-reminder',
            with: [
                'patientName'  => $patient->first_name . ' ' . $patient->last_name,
                'recall'       => $this->recall,
                'settings'     => $this->settings,
                'branding'     => $this->branding(),
            ],
        );
    }
}
