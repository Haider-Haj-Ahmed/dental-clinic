@extends('emails.layouts.base')
@section('content')
    <h1>Appointment Reminder</h1>
    <p>Hi {{ $patientName }},</p>
    <p>This is a friendly reminder that you have an appointment in <strong>{{ $timeframe }}</strong>.</p>

    <table class="detail-table">
        <tr><td>Date</td><td>{{ $appointment->start_at->format($settings->date_format) }}</td></tr>
        <tr><td>Time</td><td>{{ $appointment->start_at->format($settings->time_format) }} – {{ $appointment->end_at->format($settings->time_format) }}</td></tr>
        @if($appointment->appointmentType)<tr><td>Type</td><td>{{ $appointment->appointmentType->name }}</td></tr>@endif
        @if($appointment->provider)<tr><td>Provider</td><td>{{ $appointment->provider->name }}</td></tr>@endif
    </table>

    @if($branding['clinic_address'])
    <div class="info-box">
        <p><strong>Location:</strong> {{ $branding['clinic_address'] }}<br>
        @if($branding['clinic_phone'])<strong>Tel:</strong> {{ $branding['clinic_phone'] }}@endif</p>
    </div>
    @endif

    <p style="font-size:12px; color:#94a3b8;">
        Need to reschedule? Contact us at least {{ $settings->cancellation_policy_hours }} hours before your appointment.
    </p>
@endsection
