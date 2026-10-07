@extends('emails.layouts.base')
@section('content')
    <h1>Invoice Ready</h1>
    <p>Hi {{ $patientName }},</p>
    <p>Your invoice from {{ $branding['clinic_name'] }} is attached to this email.</p>

    <table class="detail-table">
        <tr><td>Invoice</td><td>{{ $settings->invoice_prefix }}{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td>Date</td><td>{{ $invoice->issued_at->format($settings->date_format) }}</td></tr>
        <tr><td>Total</td><td>{{ number_format($invoice->total, 2) }} {{ $settings->currency_symbol }}</td></tr>
        <tr><td>Paid</td><td>{{ number_format($amountPaid, 2) }} {{ $settings->currency_symbol }}</td></tr>
        <tr><td>Balance Due</td><td><strong>{{ number_format($invoice->total - $amountPaid, 2) }} {{ $settings->currency_symbol }}</strong></td></tr>
    </table>

    @if($invoice->total - $amountPaid > 0)
    <div class="info-box">
        <p>A balance of <strong>{{ number_format($invoice->total - $amountPaid, 2) }} {{ $settings->currency_symbol }}</strong> is outstanding.
        @if($invoice->due_at) Please settle by <strong>{{ $invoice->due_at->format($settings->date_format) }}</strong>.@endif</p>
    </div>
    @else
    <div class="info-box">
        <p>This invoice has been paid in full. Thank you.</p>
    </div>
    @endif
@endsection
