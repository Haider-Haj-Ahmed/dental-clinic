<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Prescription {{ $prescription->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; padding: 40px; }

        .header { display: table; width: 100%; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 3px solid #0d1b2a; }
        .header-left  { display: table-cell; vertical-align: top; width: 65%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .clinic-name { font-size: 20px; font-weight: bold; color: #0d1b2a; }
        .clinic-meta { font-size: 11px; color: #64748b; line-height: 1.7; margin-top: 4px; }

        .rx-title { font-size: 36px; font-weight: bold; color: #4fdbcc; line-height: 1; }
        .rx-subtitle { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
        .rx-number { font-size: 11px; color: #64748b; margin-top: 6px; }

        .patient-block { background: #f8fafc; border-left: 3px solid #4fdbcc; padding: 12px 16px; margin-bottom: 24px; }
        .patient-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
        .patient-name { font-size: 15px; font-weight: bold; color: #0d1b2a; }
        .patient-meta { font-size: 11px; color: #64748b; margin-top: 2px; }

        .issued-row { display: table; width: 100%; margin-bottom: 24px; font-size: 11px; color: #64748b; }
        .issued-cell { display: table-cell; }
        .issued-cell.right { text-align: right; }

        .items-title { font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; }

        .rx-item { padding: 14px 0; border-bottom: 1px solid #e2e8f0; }
        .rx-item:last-child { border-bottom: none; }
        .rx-drug { font-size: 14px; font-weight: bold; color: #0d1b2a; margin-bottom: 5px; }
        .rx-detail { display: table; width: 100%; margin-top: 6px; }
        .rx-detail-cell { display: table-cell; font-size: 11px; color: #64748b; width: 25%; }
        .rx-detail-label { font-weight: bold; color: #94a3b8; text-transform: uppercase; font-size: 9px; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
        .rx-instructions { margin-top: 6px; font-size: 11px; color: #475569; font-style: italic; }

        .signature-area { margin-top: 48px; display: table; width: 100%; }
        .sig-left  { display: table-cell; width: 50%; vertical-align: bottom; }
        .sig-right { display: table-cell; width: 50%; text-align: right; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #0d1b2a; padding-top: 6px; margin-top: 40px; font-size: 11px; color: #64748b; }

        .notes-block { margin-top: 20px; padding: 10px 14px; background: #fef9c3; border-radius: 4px; font-size: 11px; color: #854d0e; }

        .footer { margin-top: 36px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; line-height: 1.6; }
        .watermark { color: #e2e8f0; font-size: 11px; }
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
        <div class="rx-title">℞</div>
        <div class="rx-subtitle">Medical Prescription</div>
        <div class="rx-number">#RX-{{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>
</div>

{{-- Patient --}}
<div class="patient-block">
    <div class="patient-label">Patient</div>
    <div class="patient-name">{{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}</div>
    <div class="patient-meta">
        DOB: {{ $prescription->patient->date_of_birth ? \Carbon\Carbon::parse($prescription->patient->date_of_birth)->format($settings->date_format) : '—' }}
        &nbsp;·&nbsp;
        @if($prescription->patient->phone) Tel: {{ $prescription->patient->phone }} @endif
    </div>
</div>

{{-- Issued row --}}
<div class="issued-row">
    <div class="issued-cell">
        <strong>Date Issued:</strong> {{ $prescription->issued_at->format($settings->date_format) }}
        @if($prescription->encounter_id)
            &nbsp;·&nbsp; <strong>Encounter:</strong> #{{ $prescription->encounter_id }}
        @endif
    </div>
    <div class="issued-cell right">
        <strong>Prescribing Provider:</strong> {{ $prescription->provider->name }}<br>
        <span style="color:#94a3b8;">{{ $prescription->provider->specialty }}</span>
        @if($prescription->provider->license_number)
            &nbsp;·&nbsp; Lic: {{ $prescription->provider->license_number }}
        @endif
    </div>
</div>

{{-- Medications --}}
<div class="items-title">Prescribed Medications</div>

@foreach($prescription->items as $index => $item)
<div class="rx-item">
    <div class="rx-drug">{{ $index + 1 }}. {{ $item->drug_name }}</div>
    <div class="rx-detail">
        @if($item->dose)
        <div class="rx-detail-cell">
            <span class="rx-detail-label">Dose</span>
            {{ $item->dose }}
        </div>
        @endif
        @if($item->frequency)
        <div class="rx-detail-cell">
            <span class="rx-detail-label">Frequency</span>
            {{ $item->frequency }}
        </div>
        @endif
        @if($item->duration)
        <div class="rx-detail-cell">
            <span class="rx-detail-label">Duration</span>
            {{ $item->duration }}
        </div>
        @endif
        @if($item->quantity)
        <div class="rx-detail-cell">
            <span class="rx-detail-label">Quantity</span>
            {{ $item->quantity }}
        </div>
        @endif
    </div>
    @if($item->instructions)
    <div class="rx-instructions">⚠ {{ $item->instructions }}</div>
    @endif
</div>
@endforeach

{{-- Notes --}}
@if($prescription->notes)
<div class="notes-block">
    <strong>Clinical Notes:</strong> {{ $prescription->notes }}
</div>
@endif

{{-- Signature --}}
<div class="signature-area">
    <div class="sig-left">
        <div class="sig-line">Patient / Representative Signature</div>
    </div>
    <div class="sig-right">
        <div class="sig-line">
            {{ $prescription->provider->name }}<br>
            <span style="color:#94a3b8;">{{ $prescription->provider->specialty }}</span>
            @if($prescription->provider->license_number)
                &nbsp;·&nbsp; Lic# {{ $prescription->provider->license_number }}
            @endif
        </div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    {{ $settings->clinic_name }}
    @if($settings->clinic_address) &nbsp;·&nbsp; {{ $settings->clinic_address }} @endif
    @if($settings->clinic_phone) &nbsp;·&nbsp; Tel: {{ $settings->clinic_phone }} @endif
    <br>
    <span class="watermark">This prescription is valid for 30 days from the date of issue. Generated {{ now()->format($settings->date_format) }}.</span>
</div>

</body>
</html>
