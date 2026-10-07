@extends('emails.layouts.base')
@section('content')
    <h1>Welcome to {{ $branding['clinic_name'] }}</h1>
    <p>Hi {{ $userName }},</p>
    <p>Your staff account has been created. You can now log in to the practice management system.</p>

    <div class="info-box">
        <p><strong>Email:</strong> {{ $userEmail }}<br>
        <strong>Role:</strong> {{ $userRole }}<br>
        <strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
    </div>

    <p style="text-align:center; margin: 28px 0;">
        <a href="{{ $loginUrl }}" class="btn">Sign In Now</a>
    </p>

    <div class="alert-box">
        <p>Please change your password immediately after your first login.</p>
    </div>
@endsection
