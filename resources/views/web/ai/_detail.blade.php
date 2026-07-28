{{--
    Detail panel partial — AiAnalysisResult
    Fields from model: analysis_type, input_summary, result (array), status,
                       ai_provider, ai_model, reviewed_at, reviewer_notes,
                       patient (relation), requestedBy, reviewedBy
--}}
@php
    $typeMap = [
        'xray_analysis'           => ['border'=>'rgba(79,219,204,.35)', 'bg'=>'rgba(79,219,204,.1)',  'color'=>'#4fdbcc','icon'=>'ti-scan',          'label'=>'X-Ray Analysis'],
        'soap_suggestion'         => ['border'=>'rgba(173,198,255,.35)','bg'=>'rgba(173,198,255,.1)', 'color'=>'#adc6ff','icon'=>'ti-clipboard-list','label'=>'SOAP Suggestion'],
        'prescription_suggestion' => ['border'=>'rgba(232,168,56,.35)', 'bg'=>'rgba(232,168,56,.1)',  'color'=>'#e8a838','icon'=>'ti-pill',          'label'=>'Prescription Suggestion'],
        'perio_risk'              => ['border'=>'rgba(255,180,171,.35)','bg'=>'rgba(255,180,171,.1)', 'color'=>'#ffb4ab','icon'=>'ti-activity',      'label'=>'Perio Risk'],
    ];
    $tc = $typeMap[$result->analysis_type] ?? ['border'=>'rgba(255,255,255,.1)','bg'=>'rgba(255,255,255,.05)','color'=>'#8e9196','icon'=>'ti-sparkles','label'=>'AI Result'];

    $data        = is_array($result->result) ? $result->result : [];
    $soap        = $data['soap'] ?? null;
    $findings    = $data['findings'] ?? null;
    $drugs       = $data['drug_interactions'] ?? $data['interactions'] ?? [];
    $nuances     = $data['clinical_nuances'] ?? $data['nuances'] ?? null;
    $tags        = $data['tags'] ?? [];
    $riskLevel   = $data['risk_level'] ?? null;
    $riskScore   = $data['risk_score'] ?? null;

    $patientName = $result->patient
        ? $result->patient->first_name.' '.$result->patient->last_name
        : 'Unknown Patient';
    $patientId = str_pad($result->patient_id, 4, '0', STR_PAD_LEFT);

    $isPending   = $result->status === 'pending';
    $isAccepted  = $result->status === 'accepted';
    $isDismissed = $result->status === 'dismissed';
    $isSOAP      = $result->analysis_type === 'soap_suggestion';
    $isRx        = $result->analysis_type === 'prescription_suggestion';
@endphp

{{-- Header --}}
<div style="padding:20px;border-bottom:1px solid rgba(255,255,255,.05);flex-shrink:0;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
        <div style="width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;background:{{ $tc['bg'] }};border:1px solid {{ $tc['border'] }};color:{{ $tc['color'] }};">
            <i class="ti {{ $tc['icon'] }}"></i>
        </div>
        <span style="font-size:10px;font-weight:700;color:{{ $tc['color'] }};text-transform:uppercase;letter-spacing:.06em;font-family:var(--font-mono);">{{ $tc['label'] }}</span>
    </div>
    <div style="font-size:19px;font-weight:700;color:#e2e3df;margin-bottom:4px;letter-spacing:-.01em;">{{ $patientName }}</div>
    <div style="font-size:11px;color:#44474c;font-family:var(--font-mono);">
        #P-{{ $patientId }} · {{ $result->created_at->isToday() ? 'Today '.$result->created_at->format('H:i') : $result->created_at->format('M j H:i') }}
        ·
        <span style="color:{{ $isAccepted ? '#4fdbcc' : ($isDismissed ? '#44474c' : '#e8a838') }};">{{ ucfirst($result->status) }}</span>
    </div>
</div>

{{-- Body --}}
<div style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;">

    {{-- Drug interaction alert --}}
    @if(!empty($drugs))
        <div style="background:rgba(255,180,171,.07);border:1px solid rgba(147,0,10,.45);border-radius:12px;padding:14px;" class="reveal reveal-1">
            <div style="font-size:10px;font-weight:800;color:#ffb4ab;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;display:flex;align-items:center;gap:5px;">
                <i class="ti ti-alert-triangle" style="font-size:13px;"></i> Critical Drug Interaction
            </div>
            @foreach($drugs as $flag)
                <div style="display:flex;align-items:flex-start;gap:9px;{{ !$loop->first ? 'margin-top:8px;':'' }}">
                    <i class="ti ti-alert-triangle" style="font-size:14px;color:#ffb4ab;margin-top:1px;flex-shrink:0;"></i>
                    <div style="flex:1;font-size:12px;color:#fca5a5;line-height:1.55;">
                        {{ is_array($flag) ? ($flag['message'] ?? json_encode($flag)) : $flag }}
                    </div>
                    @if(is_array($flag) && !empty($flag['severity']))
                        <span style="font-size:10px;padding:2px 6px;border-radius:4px;font-weight:700;background:#93000a;color:#fff;text-transform:uppercase;flex-shrink:0;">{{ $flag['severity'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Perio risk score --}}
    @if($riskLevel || $riskScore)
        <div style="background:rgba(255,180,171,.05);border:1px solid rgba(255,180,171,.15);border-radius:12px;padding:14px;" class="reveal reveal-1">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Perio Risk Score</div>
            <div style="display:flex;align-items:center;gap:12px;">
                @if($riskScore)
                    <div style="font-size:28px;font-weight:800;color:#ffb4ab;">{{ $riskScore }}</div>
                @endif
                @if($riskLevel)
                    <span style="font-size:11px;padding:3px 10px;border-radius:6px;font-weight:700;background:rgba(255,180,171,.12);color:#ffb4ab;border:1px solid rgba(255,180,171,.25);">{{ strtoupper($riskLevel) }}</span>
                @endif
            </div>
        </div>
    @endif

    {{-- SOAP block --}}
    @if($soap)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden;" class="reveal reveal-2">
            @foreach(['S'=>['color'=>'#adc6ff','label'=>'Subjective'],'O'=>['color'=>'#4fdbcc','label'=>'Objective'],'A'=>['color'=>'#c9b8ff','label'=>'Assessment'],'P'=>['color'=>'#fbbf24','label'=>'Plan']] as $key=>$meta)
                @if(!empty($soap[$key]) || !empty($soap[strtolower($key)]))
                    @php $val = $soap[$key] ?? $soap[strtolower($key)] ?? ''; @endphp
                    <div style="padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.03);display:flex;gap:12px;">
                        <div style="font-size:13px;font-weight:900;width:18px;flex-shrink:0;color:{{ $meta['color'] }};text-align:center;">{{ $key }}</div>
                        <div style="font-size:12px;color:#8e9196;line-height:1.6;flex:1;">{{ $val }}</div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Findings (X-ray) --}}
    @if($findings)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-2">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Findings</div>
            <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">{{ is_array($findings) ? implode('. ', $findings) : $findings }}</div>
        </div>
    @endif

    {{-- Clinical nuances + tags --}}
    @if($nuances)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-3">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Clinical Nuances</div>
            <div style="font-size:12px;color:#c4c6cc;line-height:1.6;margin-bottom:{{ !empty($tags)?'10px':'0' }};">{{ $nuances }}</div>
            @if(!empty($tags))
                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                    @foreach($tags as $tag)
                        <span style="font-size:10px;padding:2px 7px;border-radius:4px;font-weight:600;font-family:var(--font-mono);letter-spacing:.04em;background:rgba(173,198,255,.1);color:#adc6ff;border:1px solid rgba(173,198,255,.2);">{{ strtoupper($tag) }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Fallback: just show input_summary --}}
    @if(!$soap && !$findings && !$nuances && !$riskLevel)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-2">
            <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">{{ $result->input_summary }}</div>
        </div>
    @endif

    {{-- Reviewer notes (if already reviewed) --}}
    @if($result->reviewer_notes)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:10px;padding:12px;" class="reveal reveal-4">
            <div style="font-size:10px;font-weight:700;color:#44474c;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;font-family:var(--font-mono);">Provider Notes</div>
            <div style="font-size:12px;color:#8e9196;line-height:1.55;">{{ $result->reviewer_notes }}</div>
        </div>
    @endif

</div>

{{-- Actions --}}
<div style="padding:14px;border-top:1px solid rgba(255,255,255,.05);background:rgba(10,16,14,.3);flex-shrink:0;display:flex;flex-direction:column;gap:8px;">

    @if($isPending)
        @if($isSOAP)
            {{-- Apply SOAP to encounter --}}
            <form method="POST" action="{{ route('web.ai.apply-soap', $result->id) }}"
                  hx-post="{{ route('web.ai.apply-soap', $result->id) }}"
                  hx-target="#detail-panel" hx-swap="innerHTML">
                @csrf
                <button type="submit" style="width:100%;padding:11px;border-radius:10px;border:none;cursor:pointer;background:linear-gradient(135deg,#4fdbcc,#0da291);color:#050e1f;font-size:13px;font-weight:700;transition:all .2s;box-shadow:0 4px 14px rgba(79,219,204,.25);" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 20px rgba(79,219,204,.4)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 14px rgba(79,219,204,.25)'">
                    Apply to Encounter
                </button>
            </form>
        @elseif($isRx)
            {{-- Link to create prescription form --}}
            <a href="{{ route('web.prescriptions') }}" style="display:block;width:100%;padding:11px;border-radius:10px;text-align:center;background:linear-gradient(135deg,#4fdbcc,#0da291);color:#050e1f;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 4px 14px rgba(79,219,204,.25);">
                Create Prescription
            </a>
        @else
            <form method="POST" action="{{ route('web.ai.accept', $result->id) }}"
                  hx-post="{{ route('web.ai.accept', $result->id) }}"
                  hx-target="#detail-panel" hx-swap="innerHTML">
                @csrf @method('PATCH')
                <button type="submit" style="width:100%;padding:11px;border-radius:10px;border:none;cursor:pointer;background:linear-gradient(135deg,#4fdbcc,#0da291);color:#050e1f;font-size:13px;font-weight:700;transition:all .2s;box-shadow:0 4px 14px rgba(79,219,204,.25);">
                    Accept Result
                </button>
            </form>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <a href="{{ route('web.encounters') }}" style="display:block;text-align:center;padding:9px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:#e2e3df;font-size:12px;font-weight:500;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.09)'" onmouseout="this.style.background='rgba(255,255,255,.04)'">
                Edit Manually
            </a>
            <form method="POST" action="{{ route('web.ai.dismiss', $result->id) }}" style="display:contents;"
                  hx-post="{{ route('web.ai.dismiss', $result->id) }}"
                  hx-target="#detail-panel" hx-swap="innerHTML">
                @csrf @method('PATCH')
                <button type="submit" style="padding:9px;border-radius:9px;background:rgba(255,180,171,.05);border:1px solid rgba(147,0,10,.3);color:#ffb4ab;font-size:12px;font-weight:500;cursor:pointer;transition:background .15s;width:100%;" onmouseover="this.style.background='rgba(255,180,171,.1)'" onmouseout="this.style.background='rgba(255,180,171,.05)'">
                    Dismiss
                </button>
            </form>
        </div>
    @elseif($isAccepted)
        <div style="padding:10px;border-radius:10px;background:rgba(79,219,204,.06);border:1px solid rgba(79,219,204,.15);text-align:center;font-size:12px;color:#4fdbcc;font-weight:500;">
            <i class="ti ti-check" style="margin-right:5px;"></i>Accepted{{ $result->reviewed_at ? ' · '.$result->reviewed_at->format('M j H:i') : '' }}
        </div>
    @elseif($isDismissed)
        <div style="padding:10px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);text-align:center;font-size:12px;color:#44474c;font-weight:500;">
            <i class="ti ti-x" style="margin-right:5px;"></i>Dismissed{{ $result->reviewed_at ? ' · '.$result->reviewed_at->format('M j H:i') : '' }}
        </div>
    @endif

    <p style="font-size:10px;color:#44474c;text-align:center;line-height:1.5;margin:2px 0 0;">
        AI-generated suggestion. Final clinical decisions remain with the attending provider.
    </p>
</div>
