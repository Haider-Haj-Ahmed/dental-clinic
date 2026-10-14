@extends('emails.layouts.base')
@section('content')
    <h1>Password Changed</h1>
    <p>Hi {{ $userName }},</p>
    <p>Your password was successfully changed on your {{ $branding['clinic_name'] }} account.</p>

    <table class="detail-table">
        <tr><td>Time</td><td>{{ $time }}</td></tr>
        <tr><td>IP Address</td><td>{{ $ipAddress }}</td></tr>
    </table>

    <div class="alert-box">
        <p>If you did not make this change, your account may be compromised. Please reset your password immediately and contact your administrator.</p>
    </div>
@endsection
