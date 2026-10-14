@extends('emails.layouts.base')
@section('content')
    <h1>Payment Received</h1>
    <p>Hi {{ $patientName }},</p>
    <p>We have received your payment. Thank you.</p>

    <table class="detail-table">
        <tr><td>Amount</td><td><strong>{{ number_format($payment->amount, 2) }} {{ $settings->currency_symbol }}</strong></td></tr>
        <tr><td>Date</td><td>{{ \Carbon\Carbon::parse($payment->paid_at)->format($settings->date_format) }}</td></tr>
        <tr><td>Invoice</td><td>{{ $settings->invoice_prefix }}{{ str_pad($payment->invoice_id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        @if($payment->notes)<tr><td>Notes</td><td>{{ $payment->notes }}</td></tr>@endif
    </table>

    @php $balance = $payment->invoice->total - $payment->invoice->payments->sum('amount'); @endphp
    @if($balance > 0)
    <div class="info-box">
        <p>Remaining balance: <strong>{{ number_format($balance, 2) }} {{ $settings->currency_symbol }}</strong></p>
    </div>
    @else
    <div class="info-box">
        <p>Your account is fully settled. Thank you for your prompt payment.</p>
    </div>
    @endif
@endsection
