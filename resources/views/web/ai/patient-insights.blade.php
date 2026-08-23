<x-layouts.app
    page-title="Patient AI Insights"
    active-route="web.ai"
>
<div style="height:100%;overflow:auto;padding:22px 24px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <a href="{{ route('web.ai') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:#8e9196;text-decoration:none;font-size:12px;">
            <i class="ti ti-arrow-left"></i> AI Assistant
        </a>
        <div>
            <h1 style="margin:0;color:#e2e3df;font-size:20px;font-weight:700;">{{ $patient->first_name }} {{ $patient->last_name }}</h1>
            <div style="font-size:12px;color:#44474c;font-family:var(--font-mono);">Latest AI insights by category</div>
        </div>
    </div>

    @if($riskLevel || $riskScore)
        <section style="margin-bottom:16px;padding:16px;border-radius:14px;background:rgba(255,180,171,.06);border:1px solid rgba(255,180,171,.18);">
            <div style="font-size:10px;color:#ffb4ab;font-weight:800;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">Current Perio Risk</div>
            <div style="display:flex;align-items:baseline;gap:10px;">
                @if($riskScore !== null)
                    <div style="font-size:34px;color:#ffb4ab;font-weight:800;">{{ $riskScore }}</div>
                @endif
                @if($riskLevel)
                    <div style="font-size:13px;color:#e2e3df;font-weight:700;">{{ strtoupper($riskLevel) }}</div>
                @endif
            </div>
        </section>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
        @foreach([
            'xray_analysis' => ['label' => 'X-ray Analysis', 'icon' => 'ti-scan', 'color' => '#4fdbcc'],
            'soap_suggestion' => ['label' => 'SOAP Suggestion', 'icon' => 'ti-clipboard-list', 'color' => '#adc6ff'],
            'prescription_suggestion' => ['label' => 'Prescription Suggestion', 'icon' => 'ti-pill', 'color' => '#e8a838'],
            'perio_risk' => ['label' => 'Perio Risk', 'icon' => 'ti-activity', 'color' => '#ffb4ab'],
        ] as $type => $meta)
            @php
                $result = $insights[$type] ?? null;
                $data = $result && is_array($result->result) ? $result->result : [];
                $summary = $data['overall_assessment']
                    ?? $data['clinical_notes']
                    ?? $data['general_notes']
                    ?? ($data['risk_level'] ?? null)
                    ?? $result?->input_summary;
            @endphp
            <section style="min-height:180px;padding:16px;border-radius:14px;background:rgba(10,16,14,.48);border:1px solid rgba(255,255,255,.06);">
                <div style="display:flex;align-items:center;gap:9px;margin-bottom:12px;">
                    <div style="width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:{{ $meta['color'] }};">
                        <i class="ti {{ $meta['icon'] }}"></i>
                    </div>
                    <div style="font-size:13px;color:#e2e3df;font-weight:700;">{{ $meta['label'] }}</div>
                </div>

                @if($result)
                    <div style="font-size:12px;color:#c4c6cc;line-height:1.6;margin-bottom:12px;">{{ is_array($summary) ? json_encode($summary) : $summary }}</div>
                    <div style="font-size:11px;color:#44474c;font-family:var(--font-mono);">{{ $result->created_at->format('M j, Y H:i') }} · {{ ucfirst($result->status) }}</div>
                @else
                    <div style="font-size:12px;color:#44474c;line-height:1.6;">No {{ strtolower($meta['label']) }} result has been generated for this patient.</div>
                @endif
            </section>
        @endforeach
    </div>
</div>
</x-layouts.app>
