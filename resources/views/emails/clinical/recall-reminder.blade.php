@extends('emails.layouts.base')
@section('content')
    <h1>Time for Your Dental Recall</h1>
    <p>Hi {{ $patientName }},</p>
    <p>It's time for your routine dental recall at {{ $branding['clinic_name'] }}. Regular check-ups help us keep your smile healthy.</p>

    <div class="info-box">
        <p>Your recall was due on <strong>{{ $recall->due_date->format($settings->date_format) }}</strong>.</p>
    </div>

    <p>Please contact us to book your appointment at your earliest convenience:</p>
    @if($branding['clinic_phone'])<p><strong>Tel:</strong> {{ $branding['clinic_phone'] }}</p>@endif
    @if($branding['clinic_email'])<p><strong>Email:</strong> {{ $branding['clinic_email'] }}</p>@endif
    @if($branding['clinic_address'])<p><strong>Address:</strong> {{ $branding['clinic_address'] }}</p>@endif
@endsection
