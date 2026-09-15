@extends('emails.layouts.base')

@section('content')
    <h1>
        Two-factor authentication {{ $enabled ? 'enabled' : 'disabled' }}
    </h1>

    <p>Hi {{ $userName }},</p>

    @if($enabled)
        <p>
            Two-factor authentication has been <strong>enabled</strong> on your
            {{ $branding['clinic_name'] }} account. Your account is now more secure.
        </p>
        <div class="info-box">
            <p>
                From now on, you will need your authenticator app to sign in.
                Keep your recovery codes in a safe place — they are the only way
                to access your account if you lose your device.
            </p>
        </div>
    @else
        <div class="alert-box">
            <p>
                Two-factor authentication has been <strong>disabled</strong> on your
                {{ $branding['clinic_name'] }} account.
            </p>
        </div>
        <p>
            If you did not make this change, your account may be compromised.
            Please reset your password immediately and contact your clinic administrator.
        </p>
    @endif

    <hr class="divider">

    <p style="font-size: 13px; color: #94a3b8;">
        This action was performed on <strong>{{ $time }}</strong>.
        If this was you, no further action is required.
    </p>
@endsection
