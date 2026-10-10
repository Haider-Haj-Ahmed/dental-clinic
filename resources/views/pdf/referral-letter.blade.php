<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Referral Letter</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; padding: 50px; }

        .header { display: table; width: 100%; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #0d1b2a; }
        .header-left  { display: table-cell; vertical-align: top; width: 60%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .clinic-name  { font-size: 20px; font-weight: bold; color: #0d1b2a; }
        .clinic-meta  { font-size: 11px; color: #64748b; line-height: 1.8; margin-top: 4px; }
        .ref-label    { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; }
        .ref-number   { font-size: 14px; font-weight: bold; color: #4fdbcc; margin-top: 2px; }
        .ref-date     { font-size: 11px; color: #64748b; margin-top: 6px; }

        .letter-body  { max-width: 100%; }
        .salutation   { font-size: 13px; color: #0d1b2a; margin-bottom: 18px; }
        p.body        { font-size: 12px; color: #334155; line-height: 1.8; margin-bottom: 14px; }

        .patient-box  { background: #f0fdf9; border: 1px solid #99f6e4; border-left: 4px solid #4fdbcc; border-radius: 4px; padding: 14px 18px; margin: 20px 0; }
        .patient-box .label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .patient-grid { display: table; width: 100%; }
        .patient-col  { display: table-cell; width: 50%; font-size: 11px; line-height: 1.9; }
        .patient-col strong { color: #0d1b2a; }

        .clinical-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 14px 18px; margin: 20px 0; }
        .clinical-box .section-title { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .clinical-box p { font-size: 12px; color: #334155; line-height: 1.7; }

        .urgency-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .urgency-routine  { background: #dcfce7; color: #166534; }
        .urgency-urgent   { background: #fef9c3; color: #854d0e; }
        .urgency-emergency{ background: #fee2e2; color: #991b1b; }

        .signature-area { margin-top: 48px; }
        .sig-label  { font-size: 11px; color: #64748b; margin-bottom: 4px; }
        .sig-name   { font-size: 14px; font-weight: bold; color: #0d1b2a; }
        .sig-sub    { font-size: 11px; color: #64748b; }
        .sig-line   { border-top: 1px solid #0d1b2a; padding-top: 6px; margin-top: 40px; width: 260px; }

        .footer { margin-top: 48px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; }
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
        <div class="ref-label">Referral Letter</div>
        <div class="ref-number">#REF-{{ str_pad($referral->id, 5, '0', STR_PAD_LEFT) }}</div>
        <div class="ref-date">
            Date: {{ now()->format($settings->date_format) }}<br>
            Urgency: <span class="urgency-badge urgency-{{ $referral->urgency }}">{{ ucfirst($referral->urgency) }}</span>
        </div>
    </div>
</div>

{{-- Letter body --}}
<div class="letter-body">

    {{-- To --}}
    <p class="body">
        <strong>To:</strong> {{ $referral->referred_to_name }}
        @if($referral->referred_to_specialty) — {{ $referral->referred_to_specialty }} @endif
        @if($referral->referred_to_clinic) <br>{{ $referral->referred_to_clinic }} @endif
    </p>

    <p class="salutation">Dear {{ $referral->referred_to_name }},</p>

    <p class="body">
        I am writing to refer the following patient to your care for specialist evaluation and management.
    </p>

    {{-- Patient details --}}
    <div class="patient-box">
        <div class="label">Patient Information</div>
        <div class="patient-grid">
            <div class="patient-col">
                <strong>Name:</strong> {{ $referral->patient->first_name }} {{ $referral->patient->last_name }}<br>
                @if($referral->patient->date_of_birth)
                <strong>DOB:</strong> {{ \Carbon\Carbon::parse($referral->patient->date_of_birth)->format($settings->date_format) }}<br>
                @endif
                <strong>Gender:</strong> {{ ucfirst($referral->patient->gender ?? '—') }}
            </div>
            <div class="patient-col">
                @if($referral->patient->phone)<strong>Phone:</strong> {{ $referral->patient->phone }}<br>@endif
                @if($referral->patient->email)<strong>Email:</strong> {{ $referral->patient->email }}<br>@endif
            </div>
        </div>
    </div>

    {{-- Clinical details --}}
    @if($referral->reason)
    <div class="clinical-box">
        <div class="section-title">Reason for Referral</div>
        <p>{{ $referral->reason }}</p>
    </div>
    @endif

    @if($referral->clinical_notes)
    <div class="clinical-box">
        <div class="section-title">Clinical Notes</div>
        <p>{{ $referral->clinical_notes }}</p>
    </div>
    @endif

    @if($referral->relevant_history)
    <div class="clinical-box">
        <div class="section-title">Relevant Medical History</div>
        <p>{{ $referral->relevant_history }}</p>
    </div>
    @endif

    @if($referral->requested_action)
    <div class="clinical-box">
        <div class="section-title">Requested Action</div>
        <p>{{ $referral->requested_action }}</p>
    </div>
    @endif

    <p class="body" style="margin-top: 20px;">
        Please do not hesitate to contact us should you require any additional information regarding this patient.
        We look forward to your assessment and management plan.
    </p>

    {{-- Signature --}}
    <div class="signature-area">
        <div class="sig-label">Referring Clinician</div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $referral->referringProvider->name }}</div>
        <div class="sig-sub">
            {{ $referral->referringProvider->specialty }}
            @if($referral->referringProvider->license_number)
                &nbsp;·&nbsp; Lic# {{ $referral->referringProvider->license_number }}
            @endif
            <br>{{ $settings->clinic_name }}
        </div>
    </div>

</div>

{{-- Footer --}}
<div class="footer">
    {{ $settings->clinic_name }}
    @if($settings->clinic_address) &nbsp;·&nbsp; {{ $settings->clinic_address }} @endif
    @if($settings->clinic_phone) &nbsp;·&nbsp; Tel: {{ $settings->clinic_phone }} @endif
    @if($settings->clinic_email) &nbsp;·&nbsp; {{ $settings->clinic_email }} @endif
    <br>This referral was generated on {{ now()->format($settings->date_format) }}.
    Confidential — For medical professional use only.
</div>

</body>
</html>
