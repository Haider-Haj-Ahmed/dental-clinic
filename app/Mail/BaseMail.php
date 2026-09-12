<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * BaseMail
 *
 * All application mailables extend this class.
 * Provides shared clinic branding, queue configuration,
 * and a consistent from address pulled from config.
 *
 * Every subclass must implement:
 *   - envelope()  — subject and optional reply-to
 *   - content()   — blade view and data
 *
 * Subclasses should NOT call send() directly on the HTTP request.
 * Always dispatch via SendMailJob:
 *
 *   SendMailJob::dispatch($mailable, $recipientEmail, $recipientName);
 */
abstract class BaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of times to attempt sending before marking as failed.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between retry attempts (exponential backoff).
     */
    public array $backoff = [30, 60, 120];

    /**
     * Seconds before the job is considered timed out.
     */
    public int $timeout = 30;

    /**
     * Shared branding data available in every email view via $branding.
     */
    protected function branding(): array
    {
        return [
            'clinic_name'   => config('app.clinic_name', config('app.name', 'Crystalline Dental')),
            'clinic_email'  => config('mail.from.address'),
            'clinic_phone'  => config('app.clinic_phone', ''),
            'clinic_address'=> config('app.clinic_address', ''),
            'primary_color' => config('app.clinic_primary_color', '#4fdbcc'),
            'app_url'       => config('app.url'),
            'year'          => now()->year,
        ];
    }

    /**
     * Merge branding into the view data so every template has access to it.
     */
    public function build(): static
    {
        return $this->with(['branding' => $this->branding()]);
    }
}
