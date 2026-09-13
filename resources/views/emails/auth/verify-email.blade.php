@extends('emails.layouts.base')

@section('content')
    <h1>Verify your email address</h1>

    <p>Hi {{ $userName }},</p>

    <p>
        Your staff account has been created on {{ $branding['clinic_name'] }}.
        Please verify your email address to activate your account.
    </p>

    <p style="text-align: center; margin: 32px 0;">
        <a href="{{ $verificationUrl }}" class="btn" target="_blank">
            Verify Email Address
        </a>
    </p>

    <div class="info-box">
        <p>
            This link will expire in <strong>{{ $expiresInMinutes }} minutes</strong>.
            If you did not create this account, no action is required.
        </p>
    </div>

    <hr class="divider">

    <p style="font-size: 13px; color: #94a3b8;">
        If the button above doesn't work, copy and paste this URL into your browser:<br>
        <a href="{{ $verificationUrl }}" style="color: #4fdbcc; word-break: break-all; font-size: 12px;">
            {{ $verificationUrl }}
        </a>
    </p>
@endsection
