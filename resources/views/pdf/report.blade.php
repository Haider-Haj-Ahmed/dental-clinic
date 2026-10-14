<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; padding: 30px; }

        .header { display: table; width: 100%; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #0d1b2a; }
        .header-left  { display: table-cell; vertical-align: middle; width: 60%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }

        .clinic-name  { font-size: 18px; font-weight: bold; color: #0d1b2a; }
        .clinic-meta  { font-size: 10px; color: #64748b; margin-top: 3px; }
        .report-title { font-size: 20px; font-weight: bold; color: #0d1b2a; }
        .report-meta  { font-size: 10px; color: #64748b; margin-top: 4px; }

        .filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; font-size: 11px; color: #64748b; }
        .filters strong { color: #0d1b2a; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; padding: 7px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; background: #f8fafc; }
        table.data td { padding: 8px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #1a1a2e; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }

        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
        .no-data { text-align: center; padding: 40px; color: #94a3b8; font-size: 13px; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="clinic-name">{{ $settings->clinic_name }}</div>
        <div class="clinic-meta">
            @if($settings->clinic_address) {{ $settings->clinic_address }} &nbsp;·&nbsp; @endif
            @if($settings->clinic_phone) Tel: {{ $settings->clinic_phone }} @endif
        </div>
    </div>
    <div class="header-right">
        <div class="report-title">{{ $title }}</div>
        <div class="report-meta">Generated: {{ now()->format($settings->date_format . ' ' . $settings->time_format) }}</div>
    </div>
</div>

@if(!empty($filters['date_from']) || !empty($filters['date_to']))
<div class="filters">
    <strong>Period:</strong>
    {{ $filters['date_from'] ?? '—' }} → {{ $filters['date_to'] ?? 'Today' }}
    @if(!empty($filters['provider_id'])) &nbsp;·&nbsp; <strong>Provider ID:</strong> {{ $filters['provider_id'] }} @endif
</div>
@endif

@if(empty($data))
    <div class="no-data">No data available for the selected filters.</div>
@else
    @php $headers = array_keys(is_array($data[0]) ? $data[0] : (array)$data[0]); @endphp
    <table class="data">
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                @foreach((array)$row as $value)
                    <td>{{ $value ?? '—' }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    {{ $settings->clinic_name }} &nbsp;·&nbsp; Confidential &nbsp;·&nbsp; {{ now()->format($settings->date_format) }}
</div>

</body>
</html>
