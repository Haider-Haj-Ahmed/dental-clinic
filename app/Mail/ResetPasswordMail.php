<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends BaseMail
{
    public function __construct(
        private readonly User   $user,
        private readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your password — ' . config('app.clinic_name', config('app.name')),
        );
    }

    public function content(): Content
    {
        // For a real frontend, set FRONTEND_URL in .env and point the link there.
        // For API-only usage (e.g. Postman), the email exposes the raw token
        // so it can be submitted directly to POST /api/v1/auth/reset-password.
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $resetUrl    = rtrim($frontendUrl, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email);

        return new Content(
            view: 'emails.auth.reset-password',
            with: [
                'userName' => $this->user->name,
                'resetUrl' => $resetUrl,
                'token'    => $this->token,
                'email'    => $this->user->email,
                'branding' => $this->branding(),
            ],
        );
    }
}
