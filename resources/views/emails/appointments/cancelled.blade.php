@extends('emails.layouts.base')
@section('content')
    <h1>Appointment Cancelled</h1>
    <p>Hi {{ $patientName }},</p>

    <div class="alert-box">
        <p>Your appointment has been cancelled.</p>
    </div>

    <table class="detail-table">
        <tr><td>Date</td><td>{{ $appointment->start_at->format($settings->date_format) }}</td></tr>
        <tr><td>Time</td><td>{{ $appointment->start_at->format($settings->time_format) }}</td></tr>
        @if($appointment->appointmentType)<tr><td>Type</td><td>{{ $appointment->appointmentType->name }}</td></tr>@endif
    </table>

    <p>To book a new appointment, please contact us:</p>
    @if($branding['clinic_phone'])<p><strong>Tel:</strong> {{ $branding['clinic_phone'] }}</p>@endif
    @if($branding['clinic_email'])<p><strong>Email:</strong> {{ $branding['clinic_email'] }}</p>@endif
@endsection
