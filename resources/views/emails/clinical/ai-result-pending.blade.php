@extends('emails.layouts.base')
@section('content')
    <h1>AI Result Ready for Review</h1>
    <p>An AI analysis result is ready for your review.</p>

    <table class="detail-table">
        <tr><td>Type</td><td>{{ $typeLabel }}</td></tr>
        <tr><td>Patient</td><td>{{ $patientName }}</td></tr>
        <tr><td>Status</td><td>Pending Review</td></tr>
    </table>

    <p style="text-align:center; margin: 28px 0;">
        <a href="{{ $reviewUrl }}" class="btn">Review Result</a>
    </p>

    <div class="info-box">
        <p>AI-generated results require clinical review before being applied to patient records.</p>
    </div>
@endsection
