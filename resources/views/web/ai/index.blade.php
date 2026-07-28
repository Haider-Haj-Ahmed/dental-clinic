{{--
    AI Assistant — index
    Controller: App\Http\Controllers\Web\AiAssistantController@index
    Variables:
        $results      — LengthAwarePaginator of AiAnalysisResult (with patient, requestedBy, reviewedBy)
        $activeType   — string: all|xray|soap|prescription|perio
        $activeStatus — string: all|pending|accepted|dismissed
        $selected     — AiAnalysisResult|null (first result, pre-loaded for detail panel)
        $counts       — array of counts per type
--}}

<x-layouts.app
    page-title="AI Assistant"
    active-route="web.ai"
>

@push('styles')
<style>
.type-pill {
    display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:10px;
    font-size:12px;font-weight:500;cursor:pointer;white-space:nowrap;text-decoration:none;
    border:1px solid rgba(255,255,255,.07);background:rgba(255,255,255,.03);
    color:#8e9196;transition:all .18s ease;
}
.type-pill:hover { background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.13);color:#e2e3df; }
.type-pill.active-teal { background:rgba(79,219,204,.10);border-color:rgba(79,219,204,.28);color:#4fdbcc; }
.type-pill.active-blue { background:rgba(173,198,255,.12);border-color:rgba(173,198,255,.3);color:#adc6ff; }

.result-card {
    background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);
    border-radius:12px;padding:14px;margin-bottom:10px;cursor:pointer;
    transition:all .25s cubic-bezier(.4,0,.2,1);
}
.result-card:hover { background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.12);transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.15); }
.result-card.selected { background:rgba(173,198,255,.07);border-color:rgba(173,198,255,.3);box-shadow:0 0 20px rgba(173,198,255,.07); }
</style>
@endpush

<div style="display:flex;flex-direction:column;height:100%;overflow:hidden;">

    {{-- Type filter bar --}}
    <nav style="display:flex;align-items:center;gap:8px;padding:14px 24px;overflow-x:auto;border-bottom:1px solid rgba(255,255,255,.05);flex-shrink:0;">
        @php
            $types = [
                ['key'=>'all',          'icon'=>'ti-layout-list',   'label'=>'All results',     'count'=>$counts['all']],
                ['key'=>'xray',         'icon'=>'ti-scan',           'label'=>'X-ray analysis',  'count'=>$counts['xray']],
                ['key'=>'soap',         'icon'=>'ti-clipboard-list', 'label'=>'SOAP suggestions','count'=>$counts['soap']],
                ['key'=>'prescription', 'icon'=>'ti-pill',           'label'=>'Prescriptions',   'count'=>$counts['prescription']],
                ['key'=>'perio',        'icon'=>'ti-activity',       'label'=>'Perio risk',      'count'=>$counts['perio']],
            ];
        @endphp
        @foreach($types as $t)
            @php $isActive = $activeType === $t['key']; @endphp
            <a href="{{ route('web.ai', array_merge(request()->only(['status']), ['type'=>$t['key']])) }}"
               class="type-pill {{ $isActive ? ($t['key']==='all' ? 'active-teal' : 'active-blue') : '' }}">
                <i class="ti {{ $t['icon'] }}" style="font-size:13px;"></i>
                {{ $t['label'] }}
                <span style="font-size:10px;opacity:.5;">· {{ $t['count'] }}</span>
            </a>
        @endforeach

        <div style="margin-left:auto;display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;background:rgba(173,198,255,.08);border:1px solid rgba(173,198,255,.2);font-size:11px;color:#adc6ff;font-family:var(--font-mono);flex-shrink:0;">
            <i class="ti ti-sparkles" style="font-size:12px;"></i>Gemini 2.0 Flash
        </div>
    </nav>

    {{-- Two-column layout --}}
    <div style="flex:1;display:flex;overflow:hidden;padding:20px 24px;gap:18px;">

        {{-- Results list --}}
        <section style="flex:1;display:flex;flex-direction:column;overflow:hidden;background:rgba(10,16,14,.35);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.06);border-radius:16px;min-width:0;">

            {{-- Column header --}}
            <div style="display:flex;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid rgba(255,255,255,.05);flex-shrink:0;">
                <div style="font-size:14px;font-weight:700;color:#e2e3df;flex:1;">
                    {{ ['all'=>'All results','xray'=>'X-ray analysis','soap'=>'SOAP suggestions','prescription'=>'Prescriptions','perio'=>'Perio risk'][$activeType] ?? 'All results' }}
                </div>
                <div style="font-size:11px;color:#44474c;font-family:var(--font-mono);">{{ $results->total() }} results</div>

                {{-- Status pills --}}
                <div style="display:flex;gap:4px;">
                    @foreach(['all'=>'All','pending'=>'Pending','accepted'=>'Accepted','dismissed'=>'Dismissed'] as $key=>$label)
                        <a href="{{ route('web.ai', array_merge(request()->only(['type']), ['status'=>$key])) }}"
                           style="font-size:11px;padding:4px 9px;border-radius:6px;text-decoration:none;font-weight:500;border:1px solid {{ $activeStatus===$key ? 'rgba(255,255,255,.12)':'transparent' }};background:{{ $activeStatus===$key ? 'rgba(255,255,255,.06)':'transparent' }};color:{{ $activeStatus===$key ? '#e2e3df':'#44474c' }};transition:all .15s;">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Cards --}}
            <div style="flex:1;overflow-y:auto;padding:14px;">
                @forelse($results as $index => $result)
                    @php
                        $isSelected = isset($selected) && $selected->id === $result->id;
                        $statusColor = match($result->status) {
                            'accepted'  => ['bg'=>'rgba(79,219,204,.1)', 'border'=>'rgba(79,219,204,.2)',   'color'=>'#4fdbcc'],
                            'dismissed' => ['bg'=>'rgba(255,255,255,.05)','border'=>'rgba(255,255,255,.07)','color'=>'#44474c'],
                            default     => ['bg'=>'rgba(232,168,56,.1)', 'border'=>'rgba(232,168,56,.2)',   'color'=>'#e8a838'],
                        };
                        $typeColor = match($result->analysis_type) {
                            'xray_analysis'           => ['bg'=>'rgba(79,219,204,.1)', 'border'=>'rgba(79,219,204,.3)', 'color'=>'#4fdbcc','icon'=>'ti-scan'],
                            'soap_suggestion'         => ['bg'=>'rgba(173,198,255,.1)','border'=>'rgba(173,198,255,.3)','color'=>'#adc6ff','icon'=>'ti-clipboard-list'],
                            'prescription_suggestion' => ['bg'=>'rgba(232,168,56,.1)', 'border'=>'rgba(232,168,56,.3)', 'color'=>'#e8a838','icon'=>'ti-pill'],
                            'perio_risk'              => ['bg'=>'rgba(255,180,171,.1)','border'=>'rgba(255,180,171,.3)','color'=>'#ffb4ab','icon'=>'ti-activity'],
                            default                   => ['bg'=>'rgba(255,255,255,.05)','border'=>'rgba(255,255,255,.1)','color'=>'#8e9196','icon'=>'ti-sparkles'],
                        };
                        $typeLabel = match($result->analysis_type) {
                            'xray_analysis'           => 'X-Ray',
                            'soap_suggestion'         => 'SOAP',
                            'prescription_suggestion' => 'Rx',
                            'perio_risk'              => 'Perio',
                            default                   => 'AI',
                        };
                        $patientName = $result->patient
                            ? $result->patient->first_name . ' ' . $result->patient->last_name
                            : 'Unknown patient';
                        $patientId = str_pad($result->patient_id, 4, '0', STR_PAD_LEFT);
                    @endphp
                    <div class="result-card reveal reveal-{{ min($index+1,5) }} {{ $isSelected ? 'selected':'' }}"
                         hx-get="{{ route('web.ai.show', $result->id) }}"
                         hx-target="#detail-panel"
                         hx-swap="innerHTML"
                         hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                         onclick="document.querySelectorAll('.result-card').forEach(c=>c.classList.remove('selected'));this.classList.add('selected')"
                         tabindex="0"
                         onkeydown="if(event.key==='Enter')this.click()">

                        <div style="display:flex;align-items:flex-start;gap:11px;margin-bottom:10px;">
                            <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:17px;background:{{ $typeColor['bg'] }};border:1px solid {{ $typeColor['border'] }};color:{{ $typeColor['color'] }};">
                                <i class="ti {{ $typeColor['icon'] }}"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:#e2e3df;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $typeLabel }} — {{ $result->input_summary }}
                                </div>
                                <div style="font-size:11px;color:#44474c;margin-bottom:7px;font-family:var(--font-mono);">
                                    {{ $patientName }} · #P-{{ $patientId }}
                                </div>
                                @php
                                    $resultData = is_array($result->result) ? $result->result : [];
                                    $preview = $resultData['summary'] ?? $resultData['findings'] ?? $resultData['subjective'] ?? $result->input_summary;
                                @endphp
                                <div style="font-size:12px;color:#8e9196;line-height:1.55;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    {{ $preview }}
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:8px;padding-top:10px;border-top:1px solid rgba(255,255,255,.03);">
                            <span style="font-size:11px;color:#44474c;font-family:var(--font-mono);">
                                {{ $result->created_at->isToday() ? 'Today '.$result->created_at->format('H:i') : $result->created_at->format('M j H:i') }}
                            </span>
                            <span style="font-size:10px;padding:2px 7px;border-radius:4px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:{{ $statusColor['bg'] }};border:1px solid {{ $statusColor['border'] }};color:{{ $statusColor['color'] }};">
                                {{ ucfirst($result->status) }}
                            </span>
                            <span style="margin-left:auto;font-size:10px;color:#44474c;font-family:var(--font-mono);">
                                {{ $result->ai_model ?? 'gemini-2.0-flash' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:12px;color:#44474c;padding:40px;">
                        <i class="ti ti-robot" style="font-size:40px;opacity:.3;"></i>
                        <p style="font-size:13px;margin:0;">No AI results found for this filter.</p>
                    </div>
                @endforelse

                @if($results->hasPages())
                    <div style="padding-top:12px;">{{ $results->withQueryString()->links() }}</div>
                @endif
            </div>
        </section>

        {{-- Detail panel --}}
        <section id="detail-panel" style="width:340px;flex-shrink:0;display:flex;flex-direction:column;overflow:hidden;background:rgba(10,16,14,.60);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.07);border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.3);">
            @if(isset($selected))
                @include('web.ai._detail', ['result' => $selected])
            @else
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:14px;padding:40px;text-align:center;">
                    <svg class="tooth-glow" width="44" height="52" viewBox="0 0 68 104" fill="none">
                        <path d="M0 28C0 10 8 0 20 0C26 0 30 4 34 4C38 4 42 0 48 0C60 0 68 10 68 28L60 58C58 66 52 68 48 68C44 68 40 64 34 64C28 64 24 68 20 68C16 68 10 66 8 58Z" stroke="#4fdbcc" stroke-width="3" fill="none" stroke-opacity=".35"/>
                        <path d="M14 68C12 80 14 96 18 100" stroke="#4fdbcc" stroke-width="3" stroke-linecap="round" fill="none" stroke-opacity=".35"/>
                        <path d="M54 68C56 80 54 96 50 100" stroke="#4fdbcc" stroke-width="3" stroke-linecap="round" fill="none" stroke-opacity=".35"/>
                    </svg>
                    <p style="font-size:13px;color:#44474c;margin:0;">Select a result to review</p>
                </div>
            @endif
        </section>

    </div>
</div>

</x-layouts.app>
