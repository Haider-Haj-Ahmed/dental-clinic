<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends BaseMail
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email — ' . config('app.clinic_name', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email',
            with: [
                'userName'         => $this->user->name,
                'verificationUrl'  => $this->buildVerificationUrl(),
                'expiresInMinutes' => 60,
                'branding'         => $this->branding(),
            ],
        );
    }

    private function buildVerificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'api.auth.email.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $this->user->getKey(),
                'hash' => sha1($this->user->getEmailForVerification()),
            ]
        );
    }
}
