<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeMail extends BaseMail
{
    public function __construct(
        private readonly User $user,
        private readonly string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.clinic_name', config('app.name')) . ' — Your account is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff.welcome',
            with: [
                'userName'          => $this->user->name,
                'userEmail'         => $this->user->email,
                'userRole'          => ucfirst($this->user->role),
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl'          => config('app.url') . '/login',
                'branding'          => $this->branding(),
            ],
        );
    }
}
