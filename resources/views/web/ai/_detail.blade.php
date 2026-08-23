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
    $parseError  = ! empty($data['parse_error']);

    // SOAP — flat keys from AiService, or legacy nested soap object
    $soap = $data['soap'] ?? null;
    if (! $soap && ($data['subjective'] ?? $data['objective'] ?? $data['assessment'] ?? $data['plan'] ?? null)) {
        $soap = [
            'S' => $data['subjective'] ?? '',
            'O' => $data['objective'] ?? '',
            'A' => $data['assessment'] ?? '',
            'P' => $data['plan'] ?? '',
        ];
    }

    $drugs           = $data['drug_interaction_flags'] ?? $data['drug_interactions'] ?? $data['interactions'] ?? [];
    $nuances         = $data['clinical_notes'] ?? $data['clinical_nuances'] ?? $data['nuances'] ?? null;
    $tags            = $data['tags'] ?? [];
    $riskLevel       = $data['risk_level'] ?? null;
    $riskScore       = $data['risk_score'] ?? null;
    $findings        = $data['findings'] ?? null;
    $overall         = $data['overall_assessment'] ?? null;
    $imageQuality    = $data['image_quality'] ?? null;
    $recommendations = $data['recommendations'] ?? [];
    $rxItems         = $data['suggested_items'] ?? [];
    $rxWarnings      = $data['interaction_warnings'] ?? [];
    $contraindications = $data['contraindications'] ?? [];
    $generalNotes    = $data['general_notes'] ?? null;
    $contributing    = $data['contributing_factors'] ?? [];
    $teethOfConcern  = $data['teeth_of_concern'] ?? [];
    $treatmentRecs   = $data['treatment_recommendations'] ?? [];
    $prognosis       = $data['prognosis'] ?? null;
    $disclaimer      = $data['disclaimer'] ?? null;

    $patientName = $result->patient
        ? $result->patient->first_name.' '.$result->patient->last_name
        : 'Unknown Patient';
    $patientId = str_pad($result->patient_id, 4, '0', STR_PAD_LEFT);

    $isPending   = $result->status === 'pending';
    $isAccepted  = $result->status === 'accepted';
    $isDismissed = $result->status === 'dismissed';
    $isSOAP      = $result->analysis_type === 'soap_suggestion';
    $isRx        = $result->analysis_type === 'prescription_suggestion';
    $isXray      = $result->analysis_type === 'xray_analysis';
    $isPerio     = $result->analysis_type === 'perio_risk';
    $hasContent  = $soap || $findings || $nuances || $riskLevel || $overall || ! empty($rxItems) || ! empty($contributing) || ! empty($teethOfConcern);
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

    {{-- Parse error --}}
    @if($parseError)
        <div style="background:rgba(255,180,171,.07);border:1px solid rgba(147,0,10,.45);border-radius:12px;padding:14px;" class="reveal reveal-1">
            <div style="font-size:10px;font-weight:800;color:#ffb4ab;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;display:flex;align-items:center;gap:5px;">
                <i class="ti ti-alert-triangle" style="font-size:13px;"></i> Response could not be parsed
            </div>
            <div style="font-size:12px;color:#fca5a5;line-height:1.55;">The AI response was incomplete or malformed. Try generating again.</div>
            @if(!empty($data['raw_response']))
                <pre style="margin-top:10px;font-size:10px;color:#8e9196;white-space:pre-wrap;word-break:break-word;max-height:120px;overflow:auto;">{{ $data['raw_response'] }}</pre>
            @endif
        </div>
    @endif

    {{-- Drug interaction alert --}}
    @if(!empty($drugs))
        <div style="background:rgba(255,180,171,.07);border:1px solid rgba(147,0,10,.45);border-radius:12px;padding:14px;" class="reveal reveal-1">
            <div style="font-size:10px;font-weight:800;color:#ffb4ab;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;display:flex;align-items:center;gap:5px;">
                <i class="ti ti-alert-triangle" style="font-size:13px;"></i> Drug Interaction Flags
            </div>
            @foreach($drugs as $flag)
                <div style="display:flex;align-items:flex-start;gap:9px;{{ !$loop->first ? 'margin-top:8px;':'' }}">
                    <i class="ti ti-alert-triangle" style="font-size:14px;color:#ffb4ab;margin-top:1px;flex-shrink:0;"></i>
                    <div style="flex:1;font-size:12px;color:#fca5a5;line-height:1.55;">
                        @if(is_array($flag))
                            {{ $flag['flag'] ?? $flag['warning'] ?? $flag['message'] ?? json_encode($flag) }}
                            @if(!empty($flag['drug']))<span style="color:#8e9196;"> — {{ $flag['drug'] }}</span>@endif
                        @else
                            {{ $flag }}
                        @endif
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
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                @if($riskScore !== null && $riskScore !== '')
                    <div style="font-size:28px;font-weight:800;color:#ffb4ab;">{{ $riskScore }}</div>
                @endif
                @if($riskLevel)
                    <span style="font-size:11px;padding:3px 10px;border-radius:6px;font-weight:700;background:rgba(255,180,171,.12);color:#ffb4ab;border:1px solid rgba(255,180,171,.25);">{{ strtoupper($riskLevel) }}</span>
                @endif
                @if($prognosis)
                    <span style="font-size:11px;color:#8e9196;">Prognosis: {{ ucfirst($prognosis) }}</span>
                @endif
            </div>
        </div>
    @endif

    {{-- Prescription suggestions --}}
    @if(!empty($rxItems))
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-2">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;font-family:var(--font-mono);">Suggested Items</div>
            @foreach($rxItems as $item)
                <div style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid rgba(255,255,255,.04);':'' }}">
                    <div style="font-size:13px;font-weight:700;color:#e2e3df;">{{ $item['drug_name'] ?? 'Unknown' }}</div>
                    <div style="font-size:12px;color:#8e9196;line-height:1.6;margin-top:4px;">
                        @if(!empty($item['dose'])){{ $item['dose'] }} · @endif
                        @if(!empty($item['frequency'])){{ $item['frequency'] }} · @endif
                        @if(!empty($item['duration'])){{ $item['duration'] }}@endif
                    </div>
                    @if(!empty($item['instructions']))
                        <div style="font-size:11px;color:#c4c6cc;margin-top:4px;">{{ $item['instructions'] }}</div>
                    @endif
                    @if(!empty($item['indication']))
                        <div style="font-size:11px;color:#666;margin-top:4px;">{{ $item['indication'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($rxWarnings) || !empty($contraindications))
        <div style="background:rgba(255,180,171,.05);border:1px solid rgba(255,180,171,.15);border-radius:12px;padding:14px;" class="reveal reveal-2">
            @if(!empty($rxWarnings))
                <div style="font-size:10px;font-weight:700;color:#ffb4ab;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Interaction Warnings</div>
                @foreach($rxWarnings as $warning)
                    <div style="font-size:12px;color:#fca5a5;line-height:1.55;{{ !$loop->last ? 'margin-bottom:6px;':'' }}">
                        {{ is_array($warning) ? ($warning['warning'] ?? json_encode($warning)) : $warning }}
                    </div>
                @endforeach
            @endif
            @if(!empty($contraindications))
                <div style="font-size:10px;font-weight:700;color:#ffb4ab;text-transform:uppercase;letter-spacing:.06em;margin:{{ !empty($rxWarnings) ? '12px' : '0' }} 0 8px;">Contraindications</div>
                @foreach($contraindications as $contra)
                    <div style="font-size:12px;color:#fca5a5;line-height:1.55;{{ !$loop->last ? 'margin-bottom:6px;':'' }}">
                        {{ is_array($contra) ? ($contra['reason'] ?? json_encode($contra)) : $contra }}
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    @if($generalNotes)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-3">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Clinical Notes</div>
            <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">{{ $generalNotes }}</div>
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
    @if($imageQuality || $overall)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-2">
            @if($imageQuality)
                <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;font-family:var(--font-mono);">Image Quality: {{ ucfirst($imageQuality) }}</div>
            @endif
            @if($overall)
                <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">{{ $overall }}</div>
            @endif
        </div>
    @endif

    @if(!empty($findings))
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-2">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Findings</div>
            @foreach($findings as $finding)
                <div style="font-size:12px;color:#c4c6cc;line-height:1.6;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid rgba(255,255,255,.04);':'' }}">
                    @if(is_array($finding))
                        @if(!empty($finding['tooth_number']))<strong>Tooth {{ $finding['tooth_number'] }}:</strong> @endif
                        {{ $finding['observation'] ?? json_encode($finding) }}
                        @if(!empty($finding['confidence']))<span style="color:#666;"> ({{ $finding['confidence'] }})</span>@endif
                    @else
                        {{ $finding }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($recommendations))
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-3">
            <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Recommendations</div>
            @foreach($recommendations as $rec)
                <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">• {{ $rec }}</div>
            @endforeach
        </div>
    @endif

    @if(!empty($contributing) || !empty($teethOfConcern) || !empty($treatmentRecs))
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-3">
            @if(!empty($contributing))
                <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;font-family:var(--font-mono);">Contributing Factors</div>
                @foreach($contributing as $factor)
                    <div style="font-size:12px;color:#c4c6cc;line-height:1.6;margin-bottom:4px;">
                        {{ is_array($factor) ? ($factor['factor'] ?? '').': '.($factor['detail'] ?? '') : $factor }}
                    </div>
                @endforeach
            @endif
            @if(!empty($teethOfConcern))
                <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin:12px 0 8px;font-family:var(--font-mono);">Teeth of Concern</div>
                @foreach($teethOfConcern as $tooth)
                    <div style="font-size:12px;color:#c4c6cc;line-height:1.6;margin-bottom:4px;">
                        Tooth {{ $tooth['tooth_number'] ?? '?' }} — {{ implode(', ', $tooth['issues'] ?? []) }}
                    </div>
                @endforeach
            @endif
            @if(!empty($treatmentRecs))
                <div style="font-size:10px;font-weight:700;color:#8e9196;text-transform:uppercase;letter-spacing:.06em;margin:12px 0 8px;font-family:var(--font-mono);">Treatment Recommendations</div>
                @foreach($treatmentRecs as $rec)
                    <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">• {{ $rec }}</div>
                @endforeach
            @endif
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
    @if(!$hasContent && !$parseError)
        <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;" class="reveal reveal-2">
            <div style="font-size:12px;color:#c4c6cc;line-height:1.6;">{{ $result->input_summary }}</div>
        </div>
    @endif

    @if($disclaimer)
        <p style="font-size:10px;color:#44474c;line-height:1.5;margin:0;">{{ $disclaimer }}</p>
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
