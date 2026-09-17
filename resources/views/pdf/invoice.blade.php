<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; padding: 40px; }

        .header { display: table; width: 100%; margin-bottom: 36px; }
        .header-left { display: table-cell; vertical-align: top; width: 60%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .clinic-name { font-size: 22px; font-weight: bold; color: #0d1b2a; }
        .clinic-meta { font-size: 11px; color: #64748b; line-height: 1.7; margin-top: 4px; }

        .invoice-title { font-size: 28px; font-weight: bold; color: #0d1b2a; }
        .invoice-number { font-size: 13px; color: #4fdbcc; font-weight: bold; margin-top: 4px; }
        .invoice-dates { font-size: 11px; color: #64748b; margin-top: 8px; line-height: 1.7; }

        .divider { border: none; border-top: 2px solid #e2e8f0; margin: 24px 0; }
        .thin-divider { border: none; border-top: 1px solid #e2e8f0; margin: 12px 0; }

        .parties { display: table; width: 100%; margin-bottom: 28px; }
        .party { display: table-cell; width: 50%; vertical-align: top; }
        .party-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
        .party-name { font-size: 14px; font-weight: bold; color: #0d1b2a; }
        .party-meta { font-size: 11px; color: #64748b; line-height: 1.7; margin-top: 3px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        table.items th.right { text-align: right; }
        table.items td { padding: 10px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #1a1a2e; vertical-align: top; }
        table.items td.right { text-align: right; }
        table.items tr:last-child td { border-bottom: none; }

        .totals { float: right; width: 260px; }
        .totals-row { display: table; width: 100%; padding: 5px 0; }
        .totals-label { display: table-cell; font-size: 12px; color: #64748b; }
        .totals-value { display: table-cell; text-align: right; font-size: 12px; color: #1a1a2e; }
        .totals-total .totals-label { font-size: 14px; font-weight: bold; color: #0d1b2a; }
        .totals-total .totals-value { font-size: 14px; font-weight: bold; color: #0d1b2a; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-paid     { background: #dcfce7; color: #166534; }
        .status-partial  { background: #fef9c3; color: #854d0e; }
        .status-finalized{ background: #dbeafe; color: #1e40af; }
        .status-void     { background: #fee2e2; color: #991b1b; }
        .status-draft    { background: #f1f5f9; color: #64748b; }

        .payments { margin-top: 28px; }
        .payments-title { font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }

        .footer { margin-top: 48px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; line-height: 1.6; }

        .clearfix:after { content: ""; display: table; clear: both; }
        .accent { color: #4fdbcc; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-left">
        <div class="clinic-name">{{ $settings->clinic_name }}</div>
        <div class="clinic-meta">
            @if($settings->clinic_address) {{ $settings->clinic_address }}<br>@endif
            @if($settings->clinic_phone) Tel: {{ $settings->clinic_phone }}<br>@endif
            @if($settings->clinic_email) {{ $settings->clinic_email }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="invoice-title">INVOICE</div>
        <div class="invoice-number">{{ $settings->invoice_prefix }}{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</div>
        <div class="invoice-dates">
            Issued: {{ $invoice->issued_at->format($settings->date_format) }}<br>
            @if($invoice->due_at) Due: {{ $invoice->due_at->format($settings->date_format) }}<br>@endif
            Status: <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
        </div>
    </div>
</div>

<hr class="divider">

{{-- Bill to / Provider --}}
<div class="parties">
    <div class="party">
        <div class="party-label">Bill To</div>
        <div class="party-name">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</div>
        <div class="party-meta">
            @if($invoice->patient->phone) Tel: {{ $invoice->patient->phone }}<br>@endif
            @if($invoice->patient->email) {{ $invoice->patient->email }}@endif
        </div>
    </div>
    @if($invoice->provider)
    <div class="party" style="text-align: right;">
        <div class="party-label">Treating Provider</div>
        <div class="party-name">{{ $invoice->provider->name }}</div>
        <div class="party-meta">{{ $invoice->provider->specialty }}</div>
    </div>
    @endif
</div>

{{-- Items table --}}
<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            <th class="right" style="width:60px;">Qty</th>
            <th class="right" style="width:110px;">Unit Price</th>
            <th class="right" style="width:110px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td class="right">{{ $item->qty }}</td>
            <td class="right">{{ number_format($item->unit_price, 2) }} {{ $settings->currency_symbol }}</td>
            <td class="right">{{ number_format($item->total, 2) }} {{ $settings->currency_symbol }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<div class="clearfix">
    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Subtotal</span>
            <span class="totals-value">{{ number_format($invoice->subtotal, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
        @if($invoice->discount > 0)
        <div class="totals-row">
            <span class="totals-label">Discount</span>
            <span class="totals-value">-{{ number_format($invoice->discount, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
        @endif
        @if($invoice->tax > 0)
        <div class="totals-row">
            <span class="totals-label">{{ $settings->tax_name }} ({{ $settings->tax_rate }}%)</span>
            <span class="totals-value">{{ number_format($invoice->tax, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
        @endif
        <hr class="thin-divider">
        <div class="totals-row totals-total">
            <span class="totals-label">Total</span>
            <span class="totals-value">{{ number_format($invoice->total, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
        @if($amountPaid > 0)
        <div class="totals-row" style="margin-top:4px;">
            <span class="totals-label" style="color:#166534;">Amount Paid</span>
            <span class="totals-value" style="color:#166534;">-{{ number_format($amountPaid, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
        <div class="totals-row totals-total">
            <span class="totals-label accent">Balance Due</span>
            <span class="totals-value accent">{{ number_format($invoice->total - $amountPaid, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
        @endif
    </div>
</div>

{{-- Payments --}}
@if($invoice->payments->isNotEmpty())
<div class="payments" style="clear:both; padding-top:12px;">
    <div class="payments-title">Payment History</div>
    <table class="items">
        <thead>
            <tr>
                <th>Date</th>
                <th>Notes</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $payment)
            <tr>
                <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format($settings->date_format) }}</td>
                <td>{{ $payment->notes ?? '—' }}</td>
                <td class="right">{{ number_format($payment->amount, 2) }} {{ $settings->currency_symbol }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Notes --}}
@if($invoice->notes)
<div style="margin-top:24px; padding:12px; background:#f8fafc; border-radius:4px; font-size:11px; color:#64748b;">
    <strong>Notes:</strong> {{ $invoice->notes }}
</div>
@endif

{{-- Footer --}}
<div class="footer">
    {{ $settings->clinic_name }} &nbsp;·&nbsp;
    @if($settings->clinic_address) {{ $settings->clinic_address }} &nbsp;·&nbsp; @endif
    @if($settings->clinic_phone) Tel: {{ $settings->clinic_phone }} @endif
    <br>
    This document was generated on {{ now()->format($settings->date_format) }}.
    @if($settings->clinic_email) Questions? {{ $settings->clinic_email }} @endif
</div>

</body>
</html>
