@extends('emails.layouts.base')
@section('content')
    <h1>Appointment Confirmed</h1>
    <p>Hi {{ $patientName }},</p>
    <p>Your appointment has been confirmed at {{ $branding['clinic_name'] }}.</p>

    <table class="detail-table">
        <tr><td>Date</td><td>{{ $appointment->start_at->format($settings->date_format) }}</td></tr>
        <tr><td>Time</td><td>{{ $appointment->start_at->format($settings->time_format) }} – {{ $appointment->end_at->format($settings->time_format) }}</td></tr>
        @if($appointment->appointmentType)<tr><td>Type</td><td>{{ $appointment->appointmentType->name }}</td></tr>@endif
        @if($appointment->provider)<tr><td>Provider</td><td>{{ $appointment->provider->name }}</td></tr>@endif
        @if($appointment->chief_complaint)<tr><td>Reason</td><td>{{ $appointment->chief_complaint }}</td></tr>@endif
    </table>

    @if($branding['clinic_address'])
    <div class="info-box">
        <p><strong>Location:</strong> {{ $branding['clinic_address'] }}<br>
        @if($branding['clinic_phone'])<strong>Tel:</strong> {{ $branding['clinic_phone'] }}@endif</p>
    </div>
    @endif

    <p style="font-size:12px; color:#94a3b8;">
        To reschedule or cancel, please contact us at least {{ $settings->cancellation_policy_hours }} hours before your appointment.
    </p>
@endsection
