<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #f0f4f8; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px 40px; }
        .header { background: #0d1b2a; border-radius: 12px 12px 0 0; padding: 28px 32px; display: flex; align-items: center; gap: 12px; }
        .header-logo { font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em; }
        .header-logo span { color: {{ $branding['primary_color'] ?? '#4fdbcc' }}; }
        .header-tagline { font-size: 11px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
        .body { background: #ffffff; padding: 36px 32px; }
        .footer { background: #f8fafc; border-radius: 0 0 12px 12px; padding: 20px 32px; border-top: 1px solid #e2e8f0; }
        .footer p { font-size: 12px; color: #94a3b8; line-height: 1.6; }
        .footer a { color: #4fdbcc; text-decoration: none; }
        h1 { font-size: 22px; font-weight: 700; color: #0d1b2a; margin-bottom: 8px; letter-spacing: -0.01em; }
        p { font-size: 15px; color: #475569; line-height: 1.7; margin-bottom: 16px; }
        .btn { display: inline-block; padding: 13px 28px; background: {{ $branding['primary_color'] ?? '#4fdbcc' }}; color: #050e1f; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; margin: 8px 0 20px; }
        .info-box { background: #f0fdf9; border: 1px solid #99f6e4; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .info-box p { font-size: 14px; color: #134e4a; margin: 0; }
        .alert-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .alert-box p { font-size: 14px; color: #991b1b; margin: 0; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
        .label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px; }
        .value { font-size: 15px; font-weight: 600; color: #0d1b2a; margin-bottom: 14px; }
        table.detail-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.detail-table td { padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        table.detail-table td:first-child { color: #64748b; width: 40%; }
        table.detail-table td:last-child { color: #0d1b2a; font-weight: 500; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="header-logo">
                {{ $branding['clinic_name'] ?? config('app.name') }}
            </div>
            <div class="header-tagline">Practice Management System</div>
        </div>
    </div>

    {{-- Body --}}
    <div class="body">
        @yield('content')
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>
            This email was sent by {{ $branding['clinic_name'] ?? config('app.name') }}.
            @if(!empty($branding['clinic_address']))
                <br>{{ $branding['clinic_address'] }}
            @endif
            @if(!empty($branding['clinic_phone']))
                &nbsp;·&nbsp; {{ $branding['clinic_phone'] }}
            @endif
        </p>
        <p style="margin-top: 8px;">
            If you did not expect this email, you can safely ignore it.
            @if(!empty($branding['clinic_email']))
                Questions? <a href="mailto:{{ $branding['clinic_email'] }}">{{ $branding['clinic_email'] }}</a>
            @endif
        </p>
        <p style="margin-top: 8px; color: #cbd5e1;">
            &copy; {{ $branding['year'] }} {{ $branding['clinic_name'] ?? config('app.name') }}. All rights reserved.
        </p>
    </div>

</div>
</body>
</html>
