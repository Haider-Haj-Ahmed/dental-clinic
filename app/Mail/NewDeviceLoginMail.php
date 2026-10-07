<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewDeviceLoginMail extends BaseMail
{
    public function __construct(
        private readonly User $user,
        private readonly string $deviceName,
        private readonly string $ipAddress = 'unknown',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New sign-in to your account — ' . config('app.clinic_name', config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.new-device-login',
            with: [
                'userName'   => $this->user->name,
                'deviceName' => $this->deviceName,
                'ipAddress'  => $this->ipAddress,
                'time'       => now()->format('d M Y H:i') . ' UTC',
                'branding'   => $this->branding(),
            ],
        );
    }
}
