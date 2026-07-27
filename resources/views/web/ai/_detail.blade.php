{{--
    AI result detail panel partial.
    Included in index.blade.php AND returned by AiAssistantController@show (HTMX swap).

    Variables:
        $result — AiResult model instance (with patient, encounter relations loaded)
--}}

@php
    $typeColor = match($result->result_type) {
        'soap'         => ['border'=>'rgba(173,198,255,.35)', 'bg'=>'rgba(173,198,255,.1)',  'color'=>'#adc6ff', 'icon'=>'ti-clipboard-list', 'label'=>'SOAP Suggestion'],
        'xray'         => ['border'=>'rgba(79,219,204,.35)',  'bg'=>'rgba(79,219,204,.1)',   'color'=>'#4fdbcc', 'icon'=>'ti-scan',           'label'=>'X-Ray Analysis'],
        'prescription' => ['border'=>'rgba(232,168,56,.35)',  'bg'=>'rgba(232,168,56,.1)',   'color'=>'#e8a838', 'icon'=>'ti-pill',           'label'=>'Prescription'],
        'perio'        => ['border'=>'rgba(255,180,171,.35)', 'bg'=>'rgba(255,180,171,.1)',  'color'=>'#ffb4ab', 'icon'=>'ti-activity',       'label'=>'Perio Risk'],
        default        => ['border'=>'rgba(255,255,255,.1)',  'bg'=>'rgba(255,255,255,.05)', 'color'=>'#8e9196', 'icon'=>'ti-sparkles',       'label'=>'AI Result'],
    };

    $content    = is_string($result->content) ? json_decode($result->content, true) : (array)$result->content;
    $soap       = $content['soap']             ?? null;
    $drugFlags  = $content['drug_interactions'] ?? $content['interactions'] ?? [];
    $nuances    = $content['clinical_nuances']  ?? $content['nuances']     ?? null;
    $tags       = $content['tags']              ?? [];
@endphp

{{-- ── Header ─────────────────────────────────────────── --}}
<div style="padding:20px; border-bottom:1px solid rgba(255,255,255,.05); flex-shrink:0;">
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
        <div style="
            width:28px; height:28px; border-radius:7px; flex-shrink:0;
            display:flex; align-items:center; justify-content:center; font-size:13px;
            background:{{ $typeColor['bg'] }}; border:1px solid {{ $typeColor['border'] }}; color:{{ $typeColor['color'] }};
        ">
            <i class="ti {{ $typeColor['icon'] }}"></i>
        </div>
        <span style="font-size:10px; font-weight:700; color:{{ $typeColor['color'] }}; text-transform:uppercase; letter-spacing:.06em; font-family:'JetBrains Mono',monospace;">
            {{ $typeColor['label'] }}
        </span>
    </div>

    <div style="font-size:19px; font-weight:700; color:#e2e3df; margin-bottom:4px; letter-spacing:-.01em;">
        {{ $result->patient?->full_name ?? 'Unknown Patient' }}
    </div>
    <div style="font-size:11px; color:#44474c; font-family:'JetBrains Mono',monospace;">
        {{ $result->title }} · {{ $result->created_at->isToday() ? 'Today ' . $result->created_at->format('H:i') : $result->created_at->format('M j H:i') }}
        · <span style="
            color:{{ $result->status==='accepted'?'#4fdbcc':($result->status==='dismissed'?'#44474c':'#e8a838') }};
          ">{{ ucfirst($result->status) }}</span>
    </div>
</div>

{{-- ── Body ─────────────────────────────────────────────── --}}
<div style="flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:13px;">

    {{-- Drug interaction alert --}}
    @if(!empty($drugFlags))
        <div class="alert-banner reveal reveal-1">
            <div style="font-size:10px; font-weight:800; color:#ffb4ab; text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                <i class="ti ti-alert-triangle" style="font-size:13px;"></i>
                Critical Drug Interaction
            </div>
            @foreach($drugFlags as $flag)
                <div style="display:flex; align-items:flex-start; gap:9px; {{ !$loop->first ? 'margin-top:8px;' : '' }}">
                    <i class="ti ti-alert-triangle" style="font-size:15px; color:#ffb4ab; margin-top:1px; flex-shrink:0;"></i>
                    <div style="flex:1; font-size:12px; color:#fca5a5; line-height:1.55;">
                        {!! $flag['message'] ?? $flag !!}
                    </div>
                    @if(!empty($flag['severity']))
                        <span style="font-size:10px; padding:2px 6px; border-radius:4px; font-weight:700; background:#93000a; color:#fff; text-transform:uppercase; flex-shrink:0;">
                            {{ $flag['severity'] }}
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- SOAP block --}}
    @if($soap)
        <div class="soap-block reveal reveal-2">
            @foreach(['S'=>['label'=>'S','color'=>'#adc6ff'],'O'=>['label'=>'O','color'=>'#4fdbcc'],'A'=>['label'=>'A','color'=>'#c9b8ff'],'P'=>['label'=>'P','color'=>'#fbbf24']] as $key => $meta)
                @if(!empty($soap[$key]))
                    <div class="soap-row">
                        <div style="font-size:13px; font-weight:900; width:18px; flex-shrink:0; color:{{ $meta['color'] }}; text-align:center;">
                            {{ $meta['label'] }}
                        </div>
                        <div style="font-size:12px; color:#8e9196; line-height:1.6; flex:1;">
                            {{ $soap[$key] }}
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Clinical nuances --}}
    @if($nuances)
        <div class="finding-block reveal reveal-3">
            <div style="font-size:10px; font-weight:700; color:#8e9196; text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; font-family:'JetBrains Mono',monospace;">
                Clinical Nuances
            </div>
            <div style="font-size:12px; color:#c4c6cc; line-height:1.6; margin-bottom:10px;">
                {{ $nuances }}
            </div>
            @if(!empty($tags))
                <div style="display:flex; gap:5px; flex-wrap:wrap;">
                    @foreach($tags as $tag)
                        <span style="
                            font-size:10px; padding:2px 7px; border-radius:4px; font-weight:600;
                            font-family:'JetBrains Mono',monospace; letter-spacing:.04em;
                            background:rgba(173,198,255,.1); color:#adc6ff; border:1px solid rgba(173,198,255,.2);
                        ">{{ strtoupper($tag) }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Raw summary fallback --}}
    @if(!$soap && !$nuances)
        <div class="finding-block reveal reveal-2">
            <div style="font-size:12px; color:#c4c6cc; line-height:1.6;">
                {{ $result->summary }}
            </div>
        </div>
    @endif

</div>

{{-- ── Actions ──────────────────────────────────────────── --}}
<div style="padding:14px; border-top:1px solid rgba(255,255,255,.05); background:rgba(10,16,14,.3); flex-shrink:0; display:flex; flex-direction:column; gap:10px;">

    @if($result->status !== 'accepted')
        <form method="POST" action="{{ route('web.ai.accept', $result->id) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="act-primary">
                Apply to Encounter
            </button>
        </form>
    @endif

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
        <a href="{{ route('web.encounters.show', $result->encounter_id ?? 0) }}" class="act-secondary" style="text-align:center; text-decoration:none; display:block;">
            Edit Manually
        </a>

        @if($result->status !== 'dismissed')
            <form method="POST" action="{{ route('web.ai.dismiss', $result->id) }}" style="display:contents;">
                @csrf
                @method('PATCH')
                <button type="submit" class="act-danger">Dismiss</button>
            </form>
        @else
            <div style="flex:1;"></div>
        @endif
    </div>

    <p style="font-size:10px; color:#44474c; text-align:center; line-height:1.5; margin:0; padding-top:2px;">
        AI-generated clinical suggestion. Final decisions remain with the attending provider.
    </p>
</div>
