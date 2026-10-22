@extends('emails.layouts.base')
@section('content')
    <h1>Reset Your Password</h1>
    <p>Hi {{ $userName }},</p>
    <p>We received a request to reset your password for your {{ $branding['clinic_name'] }} account. Click the button below to proceed.</p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{!! $resetUrl !!}" class="btn" target="_blank">Reset Password</a>
    </div>

    <p style="font-size: 13px; color: #94a3b8;">Or copy this link into your browser:</p>
    <a href="{!! $resetUrl !!}" style="color: #4fdbcc; word-break: break-all; font-size: 12px;">
        {!! $resetUrl !!}
    </a>

    <div style="margin-top: 32px; padding: 16px; background: #1e293b; border-radius: 8px; border: 1px solid #334155;">
        <p style="font-size: 12px; color: #94a3b8; margin: 0 0 8px 0;">For API / Postman testing — submit these to <code>POST /api/v1/auth/reset-password</code>:</p>
        <p style="font-size: 12px; color: #e2e8f0; margin: 4px 0;"><strong>token:</strong> {{ $token }}</p>
        <p style="font-size: 12px; color: #e2e8f0; margin: 4px 0;"><strong>email:</strong> {{ $email }}</p>
    </div>

    <div class="alert-box" style="margin-top: 24px;">
        <p>If you did not request a password reset, no action is needed. This link expires in 60 minutes.</p>
    </div>
@endsection
