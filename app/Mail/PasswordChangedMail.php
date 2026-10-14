<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordChangedMail extends BaseMail
{
    public function __construct(
        private readonly User $user,
        private readonly string $ipAddress = 'unknown',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your password was changed — ' . config('app.clinic_name', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.password-changed',
            with: [
                'userName'  => $this->user->name,
                'ipAddress' => $this->ipAddress,
                'time'      => now()->format('d M Y H:i') . ' UTC',
                'branding'  => $this->branding(),
            ],
        );
    }
}
