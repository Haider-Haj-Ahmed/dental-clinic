<?php

namespace App\Jobs;

use App\Mail\BaseMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * SendMailJob
 *
 * Single queued job that wraps ALL outgoing mail.
 * Never call Mail::send() or Mail::to()->send() directly on an HTTP request.
 *
 * Usage:
 *   SendMailJob::dispatch($mailable, 'patient@email.com', 'Ahmad Khalil');
 *
 *   // With delay:
 *   SendMailJob::dispatch($mailable, $email, $name)->delay(now()->addMinutes(5));
 *
 *   // On a specific queue:
 *   SendMailJob::dispatch($mailable, $email, $name)->onQueue('mail');
 */
class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to attempt the job before failing.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between retries (exponential backoff).
     */
    public array $backoff = [30, 60, 120];

    /**
     * Seconds before this job is considered timed out.
     */
    public int $timeout = 60;

    /**
     * @param BaseMail $mailable      The mailable instance to send
     * @param string   $toAddress     Recipient email address
     * @param string   $toName        Recipient display name
     */
    public function __construct(
        private readonly BaseMail $mailable,
        private readonly string $toAddress,
        private readonly string $toName = '',
    ) {}

    public function handle(): void
    {
        Mail::to($this->toAddress, $this->toName ?: null)
            ->send($this->mailable);
    }

    /**
     * Handle a job failure — logs the failure.
     * In a future step this can fire a MailFailed event or notify the owner.
     */
    public function failed(Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('SendMailJob failed', [
            'to'        => $this->toAddress,
            'mailable'  => get_class($this->mailable),
            'error'     => $exception->getMessage(),
            'attempt'   => $this->attempts(),
        ]);
    }
}
