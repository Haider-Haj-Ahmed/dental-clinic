@extends('emails.layouts.base')
@section('content')
    <h1>Your Login Code</h1>
    <p>Hi {{ $patientName }},</p>
    <p>Use the code below to sign in to your patient portal.</p>

    <div style="text-align: center; margin: 32px 0;">
        <div style="display: inline-block; background: #0d1b2a; color: #4fdbcc; font-size: 36px; font-weight: 800; letter-spacing: 12px; padding: 20px 32px; border-radius: 8px; font-family: 'Courier New', monospace;">
            {{ $otp }}
        </div>
    </div>

    <div class="info-box">
        <p>This code expires in <strong>{{ $expiresIn }} minutes</strong>. Do not share it with anyone.</p>
    </div>

    <p style="font-size: 12px; color: #94a3b8; margin-top: 16px;">
        If you did not request this code, you can safely ignore this email.
    </p>
@endsection
