<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Security alert sent when 2FA is enabled or disabled on an account.
 *
 * Dispatched via:
 *   Mail::to($user->email, $user->name)->queue(new TwoFactorEnabledMail($user, $enabled));
 */
class TwoFactorEnabledMail extends BaseMail
{
    public function __construct(
        private readonly User $user,
        private readonly bool $enabled,
    ) {}

    public function envelope(): Envelope
    {
        $action = $this->enabled ? 'enabled' : 'disabled';
        return new Envelope(
            subject: "Two-factor authentication {$action} — " . config('app.clinic_name', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.two-factor-enabled',
            with: [
                'userName' => $this->user->name,
                'enabled'  => $this->enabled,
                'time'     => now()->format('d M Y H:i') . ' UTC',
                'branding' => $this->branding(),
            ],
        );
    }
}
