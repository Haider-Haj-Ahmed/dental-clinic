<x-layouts.app
    page-title="Patient AI Results"
    active-route="web.ai"
>
<div style="height:100%;overflow:auto;padding:22px 24px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <a href="{{ route('web.ai') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:#8e9196;text-decoration:none;font-size:12px;">
            <i class="ti ti-arrow-left"></i> AI Assistant
        </a>
        <div>
            <h1 style="margin:0;color:#e2e3df;font-size:20px;font-weight:700;">{{ $patient->first_name }} {{ $patient->last_name }}</h1>
            <div style="font-size:12px;color:#44474c;font-family:var(--font-mono);">AI results for patient #P-{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        <select name="analysis_type" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:9px;color:#e2e3df;padding:9px 10px;font-size:12px;">
            <option value="">All types</option>
            @foreach([
                'xray_analysis' => 'X-ray analysis',
                'soap_suggestion' => 'SOAP suggestion',
                'prescription_suggestion' => 'Prescription suggestion',
                'perio_risk' => 'Perio risk',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(request('analysis_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:9px;color:#e2e3df;padding:9px 10px;font-size:12px;">
            <option value="">All statuses</option>
            @foreach(['pending' => 'Pending', 'accepted' => 'Accepted', 'dismissed' => 'Dismissed'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" style="padding:9px 13px;border-radius:9px;border:0;background:#4fdbcc;color:#050e1f;font-size:12px;font-weight:700;cursor:pointer;">Filter</button>
    </form>

    <section style="background:rgba(10,16,14,.48);border:1px solid rgba(255,255,255,.06);border-radius:14px;overflow:hidden;">
        @forelse($results as $result)
            <a href="{{ route('web.ai', ['type' => match($result->analysis_type) {
                'xray_analysis' => 'xray',
                'soap_suggestion' => 'soap',
                'prescription_suggestion' => 'prescription',
                'perio_risk' => 'perio',
                default => 'all',
            }, 'status' => $result->status]) }}" style="display:flex;gap:12px;align-items:flex-start;padding:15px 16px;border-bottom:1px solid rgba(255,255,255,.05);text-decoration:none;">
                <div style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:rgba(173,198,255,.1);border:1px solid rgba(173,198,255,.25);color:#adc6ff;">
                    <i class="ti ti-sparkles"></i>
                </div>
                <div style="min-width:0;flex:1;">
                    <div style="font-size:13px;color:#e2e3df;font-weight:700;margin-bottom:3px;">{{ str_replace('_', ' ', ucfirst($result->analysis_type)) }}</div>
                    <div style="font-size:12px;color:#8e9196;line-height:1.5;">{{ $result->input_summary }}</div>
                    <div style="margin-top:7px;font-size:11px;color:#44474c;font-family:var(--font-mono);">{{ $result->created_at->format('M j, Y H:i') }} · {{ ucfirst($result->status) }}</div>
                </div>
            </a>
        @empty
            <div style="padding:42px;text-align:center;color:#44474c;font-size:13px;">No AI results found for this patient.</div>
        @endforelse
    </section>

    @if($results->hasPages())
        <div style="margin-top:14px;">{{ $results->links() }}</div>
    @endif
</div>
</x-layouts.app>
