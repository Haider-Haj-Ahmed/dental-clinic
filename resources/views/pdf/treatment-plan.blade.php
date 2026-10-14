<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Treatment Plan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; padding: 40px; }

        .header { display: table; width: 100%; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 3px solid #0d1b2a; }
        .header-left  { display: table-cell; vertical-align: top; width: 65%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .clinic-name { font-size: 20px; font-weight: bold; color: #0d1b2a; }
        .clinic-meta { font-size: 11px; color: #64748b; line-height: 1.7; margin-top: 4px; }

        .doc-title    { font-size: 24px; font-weight: bold; color: #0d1b2a; }
        .doc-number   { font-size: 12px; color: #4fdbcc; font-weight: bold; margin-top: 4px; }
        .doc-date     { font-size: 11px; color: #64748b; margin-top: 6px; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-draft     { background: #f1f5f9; color: #64748b; }
        .status-presented { background: #dbeafe; color: #1e40af; }
        .status-accepted  { background: #dcfce7; color: #166534; }
        .status-rejected  { background: #fee2e2; color: #991b1b; }

        .parties { display: table; width: 100%; margin: 20px 0 28px; }
        .party   { display: table-cell; width: 50%; vertical-align: top; }
        .party-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 5px; }
        .party-name  { font-size: 14px; font-weight: bold; color: #0d1b2a; }
        .party-meta  { font-size: 11px; color: #64748b; line-height: 1.7; margin-top: 2px; }

        .plan-title  { font-size: 16px; font-weight: bold; color: #0d1b2a; margin-bottom: 6px; }
        .plan-notes  { font-size: 12px; color: #475569; margin-bottom: 20px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        table.items th.right { text-align: right; }
        table.items td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #1a1a2e; vertical-align: top; }
        table.items td.right { text-align: right; }
        table.items td.muted  { color: #94a3b8; font-size: 11px; }
        .priority-high   { color: #991b1b; font-weight: bold; }
        .priority-medium { color: #854d0e; }
        .priority-low    { color: #166534; }

        .totals { float: right; width: 260px; margin-top: 8px; }
        .totals-row   { display: table; width: 100%; padding: 5px 0; }
        .totals-label { display: table-cell; font-size: 13px; font-weight: bold; color: #0d1b2a; }
        .totals-value { display: table-cell; text-align: right; font-size: 13px; font-weight: bold; color: #4fdbcc; }

        .timeline { margin: 28px 0; padding: 16px; background: #f8fafc; border-radius: 6px; }
        .timeline-title { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }
        .timeline-row { display: table; width: 100%; font-size: 11px; padding: 4px 0; }
        .timeline-label { display: table-cell; color: #64748b; width: 40%; }
        .timeline-value { display: table-cell; color: #0d1b2a; font-weight: 500; }

        .signature-area { margin-top: 48px; display: table; width: 100%; }
        .sig-left  { display: table-cell; width: 50%; vertical-align: bottom; }
        .sig-right { display: table-cell; width: 50%; text-align: right; vertical-align: bottom; }
        .sig-line  { border-top: 1px solid #0d1b2a; padding-top: 6px; margin-top: 40px; font-size: 11px; color: #64748b; }

        .footer { margin-top: 36px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; line-height: 1.6; }
        .clearfix:after { content: ""; display: table; clear: both; }
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
        <div class="doc-title">Treatment Plan</div>
        <div class="doc-number">#TP-{{ str_pad($plan->id, 5, '0', STR_PAD_LEFT) }}</div>
        <div class="doc-date">
            Created: {{ $plan->created_at->format($settings->date_format) }}<br>
            Status: <span class="status-badge status-{{ $plan->status }}">{{ ucfirst($plan->status) }}</span>
        </div>
    </div>
</div>

{{-- Parties --}}
<div class="parties">
    <div class="party">
        <div class="party-label">Patient</div>
        <div class="party-name">{{ $plan->patient->first_name }} {{ $plan->patient->last_name }}</div>
        <div class="party-meta">
            @if($plan->patient->date_of_birth) DOB: {{ \Carbon\Carbon::parse($plan->patient->date_of_birth)->format($settings->date_format) }}<br>@endif
            @if($plan->patient->phone) Tel: {{ $plan->patient->phone }}@endif
        </div>
    </div>
    <div class="party" style="text-align: right;">
        <div class="party-label">Treating Provider</div>
        <div class="party-name">{{ $plan->provider->name }}</div>
        <div class="party-meta">
            {{ $plan->provider->specialty }}<br>
            @if($plan->provider->license_number) Lic: {{ $plan->provider->license_number }}@endif
        </div>
    </div>
</div>

{{-- Plan title + notes --}}
<div class="plan-title">{{ $plan->title }}</div>
@if($plan->notes)
<div class="plan-notes">{{ $plan->notes }}</div>
@endif

{{-- Items table --}}
<table class="items">
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th>Procedure</th>
            <th>Tooth / Area</th>
            <th>Priority</th>
            <th class="right" style="width:110px;">Fee</th>
        </tr>
    </thead>
    <tbody>
        @foreach($plan->items->sortBy('sort_order') as $i => $item)
        <tr>
            <td class="muted">{{ $i + 1 }}</td>
            <td>
                {{ $item->description }}
                @if($item->notes) <br><span class="muted">{{ $item->notes }}</span>@endif
            </td>
            <td class="muted">{{ $item->tooth_notation ?? '—' }}</td>
            <td>
                @if($item->priority === 'high')
                    <span class="priority-high">High</span>
                @elseif($item->priority === 'medium')
                    <span class="priority-medium">Medium</span>
                @else
                    <span class="priority-low">Low</span>
                @endif
            </td>
            <td class="right">{{ number_format($item->fee, 2) }} {{ $settings->currency_symbol }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Total --}}
<div class="clearfix">
    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Total Treatment Fee</span>
            <span class="totals-value">{{ number_format($plan->total_fee, 2) }} {{ $settings->currency_symbol }}</span>
        </div>
    </div>
</div>

{{-- Timeline --}}
@if($plan->presented_at || $plan->accepted_at || $plan->rejected_at)
<div class="timeline" style="clear:both; margin-top: 20px;">
    <div class="timeline-title">Plan History</div>
    @if($plan->presented_at)
    <div class="timeline-row">
        <span class="timeline-label">Presented to patient</span>
        <span class="timeline-value">{{ $plan->presented_at->format($settings->date_format) }}</span>
    </div>
    @endif
    @if($plan->accepted_at)
    <div class="timeline-row">
        <span class="timeline-label">Accepted by patient</span>
        <span class="timeline-value">{{ $plan->accepted_at->format($settings->date_format) }}</span>
    </div>
    @endif
    @if($plan->rejected_at)
    <div class="timeline-row">
        <span class="timeline-label">Declined by patient</span>
        <span class="timeline-value">{{ $plan->rejected_at->format($settings->date_format) }}</span>
    </div>
    @endif
</div>
@endif

{{-- Signature --}}
<div class="signature-area">
    <div class="sig-left">
        <div class="sig-line">Patient Signature &amp; Date</div>
    </div>
    <div class="sig-right">
        <div class="sig-line">
            {{ $plan->provider->name }}<br>
            <span style="color:#94a3b8;">{{ $plan->provider->specialty }}</span>
        </div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    {{ $settings->clinic_name }}
    @if($settings->clinic_address) &nbsp;·&nbsp; {{ $settings->clinic_address }} @endif
    @if($settings->clinic_phone) &nbsp;·&nbsp; Tel: {{ $settings->clinic_phone }} @endif
    <br>Generated {{ now()->format($settings->date_format) }}. This treatment plan is valid for 90 days.
</div>

</body>
</html>
