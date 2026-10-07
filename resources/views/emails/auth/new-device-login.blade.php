@extends('emails.layouts.base')
@section('content')
    <h1>New Sign-In Detected</h1>
    <p>Hi {{ $userName }},</p>
    <p>A new sign-in to your {{ $branding['clinic_name'] }} account was detected.</p>

    <table class="detail-table">
        <tr><td>Device</td><td>{{ $deviceName }}</td></tr>
        <tr><td>IP Address</td><td>{{ $ipAddress }}</td></tr>
        <tr><td>Time</td><td>{{ $time }}</td></tr>
    </table>

    <div class="info-box">
        <p>If this was you, no action is needed.</p>
    </div>

    <div class="alert-box">
        <p>If you did not sign in, reset your password immediately and revoke active sessions from your account settings.</p>
    </div>
@endsection
