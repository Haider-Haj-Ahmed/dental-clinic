{{--
    AI Assistant — index view
    Route:      web.ai (GET /dashboard/ai)
    Controller: App\Http\Controllers\Web\AiAssistantController@index
    Data:
        $results      — paginated collection of AiResult models (with patient relation)
        $activeType   — current filter type string (all|xray|soap|prescription|perio)
        $activeStatus — current status filter (all|pending|accepted|dismissed)
        $selected     — currently selected AiResult model (first by default)
        $counts       — associative array of counts per type
--}}

<x-layouts.app
    :page-title="'AI Assistant'"
    :active-route="'web.ai'"
>
    @push('styles')
    <style>
        /* ── Type filter bar ───────────────────────────────── */
        .type-pill {
            display:flex; align-items:center; gap:6px;
            padding:7px 14px; border-radius:10px;
            font-size:12px; font-weight:500; cursor:pointer;
            border:1px solid rgba(255,255,255,.07);
            background:rgba(255,255,255,.03);
            color:#8e9196; white-space:nowrap;
            text-decoration:none; transition:all .18s ease;
        }
        .type-pill:hover { background:rgba(255,255,255,.07); border-color:rgba(255,255,255,.13); color:#e2e3df; }
        .type-pill.active-blue { background:rgba(173,198,255,.12); border-color:rgba(173,198,255,.3); color:#adc6ff; }
        .type-pill.active-teal { background:rgba(79,219,204,.10); border-color:rgba(79,219,204,.28); color:#4fdbcc; }

        /* ── Result card ───────────────────────────────────── */
        .result-card {
            background:rgba(255,255,255,.02);
            border:1px solid rgba(255,255,255,.06);
            border-radius:12px; padding:14px;
            margin-bottom:10px; cursor:pointer;
            transition:all .25s cubic-bezier(.4,0,.2,1);
        }
        .result-card:hover {
            background:rgba(255,255,255,.04);
            border-color:rgba(255,255,255,.12);
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(0,0,0,.15);
        }
        .result-card.selected {
            background:rgba(173,198,255,.07);
            border-color:rgba(173,198,255,.3);
            box-shadow:0 0 20px rgba(173,198,255,.07);
        }

        /* ── Status chips ──────────────────────────────────── */
        .chip-pending   { background:rgba(232,168,56,.1);  color:#e8a838; border:1px solid rgba(232,168,56,.2); }
        .chip-accepted  { background:rgba(79,219,204,.1);  color:#4fdbcc; border:1px solid rgba(79,219,204,.2); }
        .chip-dismissed { background:rgba(255,255,255,.05);color:#44474c; border:1px solid rgba(255,255,255,.07); }

        /* ── Alert / drug interaction banner ──────────────── */
        .alert-banner {
            background:rgba(255,180,171,.07);
            border:1px solid rgba(147,0,10,.45);
            border-radius:12px; padding:14px;
        }

        /* ── SOAP block ────────────────────────────────────── */
        .soap-block {
            background:rgba(255,255,255,.02);
            border:1px solid rgba(255,255,255,.06);
            border-radius:12px; overflow:hidden;
        }
        .soap-row { padding:11px 14px; border-bottom:1px solid rgba(255,255,255,.03); display:flex; gap:12px; }
        .soap-row:last-child { border-bottom:none; }

        /* ── Finding / nuance block ────────────────────────── */
        .finding-block {
            background:rgba(255,255,255,.02);
            border:1px solid rgba(255,255,255,.06);
            border-radius:12px; padding:14px;
        }

        /* ── Action buttons ────────────────────────────────── */
        .act-primary {
            width:100%; padding:11px; border-radius:10px; border:none; cursor:pointer;
            background:linear-gradient(135deg,#4fdbcc,#0da291);
            color:#050e1f; font-size:13px; font-weight:700;
            transition:all .2s; box-shadow:0 4px 14px rgba(79,219,204,.25);
        }
        .act-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(79,219,204,.4); }

        .act-secondary {
            flex:1; padding:9px; border-radius:9px;
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);
            color:#e2e3df; font-size:12px; font-weight:500; cursor:pointer;
            transition:background .15s;
        }
        .act-secondary:hover { background:rgba(255,255,255,.09); }

        .act-danger {
            flex:1; padding:9px; border-radius:9px;
            background:rgba(255,180,171,.05); border:1px solid rgba(147,0,10,.3);
            color:#ffb4ab; font-size:12px; font-weight:500; cursor:pointer;
            transition:background .15s;
        }
        .act-danger:hover { background:rgba(255,180,171,.1); }
    </style>
    @endpush

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- Page shell                                          --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div style="display:flex; flex-direction:column; height:100%; overflow:hidden;">

        {{-- ── Type filter bar ──────────────────────────────── --}}
        <nav style="
            display:flex; align-items:center; gap:8px;
            padding:14px 24px; overflow-x:auto;
            border-bottom:1px solid rgba(255,255,255,.05);
            flex-shrink:0;
        ">
            @php
                $types = [
                    ['key'=>'all',          'icon'=>'ti-layout-list',   'label'=>'All results',    'count'=>$counts['all']          ?? 0],
                    ['key'=>'xray',         'icon'=>'ti-scan',           'label'=>'X-ray analysis', 'count'=>$counts['xray']         ?? 0],
                    ['key'=>'soap',         'icon'=>'ti-clipboard-list', 'label'=>'SOAP suggestions','count'=>$counts['soap']        ?? 0],
                    ['key'=>'prescription', 'icon'=>'ti-pill',           'label'=>'Prescriptions',  'count'=>$counts['prescription'] ?? 0],
                    ['key'=>'perio',        'icon'=>'ti-activity',       'label'=>'Perio risk',     'count'=>$counts['perio']        ?? 0],
                ];
            @endphp

            @foreach($types as $t)
                @php
                    $isCurrent = $activeType === $t['key'];
                    $pillClass = $isCurrent ? ($t['key'] === 'all' ? 'type-pill active-teal' : 'type-pill active-blue') : 'type-pill';
                @endphp
                <a href="{{ route('web.ai', array_merge(request()->query(), ['type'=>$t['key'], 'status'=>$activeStatus])) }}"
                   class="{{ $pillClass }}">
                    <i class="ti {{ $t['icon'] }}" style="font-size:13px;"></i>
                    {{ $t['label'] }}
                    <span style="font-size:10px; opacity:.5;">· {{ $t['count'] }}</span>
                </a>
            @endforeach

            {{-- Model badge --}}
            <div style="margin-left:auto; display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:10px; background:rgba(173,198,255,.08); border:1px solid rgba(173,198,255,.2); font-size:11px; color:#adc6ff; font-family:'JetBrains Mono',monospace; flex-shrink:0;">
                <i class="ti ti-sparkles" style="font-size:12px;"></i>
                Gemini 2.0 Flash
            </div>
        </nav>

        {{-- ── Main two-column layout ───────────────────────── --}}
        <div style="flex:1; display:flex; overflow:hidden; padding:20px 24px; gap:18px;">

            {{-- ─── Results column ─────────────────────────── --}}
            <section style="
                flex:1; display:flex; flex-direction:column; overflow:hidden;
                background:rgba(10,16,14,.35); backdrop-filter:blur(12px);
                border:1px solid rgba(255,255,255,.06); border-radius:16px;
                min-width:0;
            ">
                {{-- Column header --}}
                <div style="display:flex; align-items:center; gap:12px; padding:16px 18px; border-bottom:1px solid rgba(255,255,255,.05); flex-shrink:0;">
                    <div style="font-size:14px; font-weight:700; color:#e2e3df; flex:1;">
                        @php
                            $typeLabels = ['all'=>'All results','xray'=>'X-ray analysis','soap'=>'SOAP suggestions','prescription'=>'Prescriptions','perio'=>'Perio risk'];
                        @endphp
                        {{ $typeLabels[$activeType] ?? 'All results' }}
                    </div>
                    <div style="font-size:11px; color:#44474c; font-family:'JetBrains Mono',monospace;">
                        {{ $results->total() }} results
                    </div>

                    {{-- Status filter pills --}}
                    <div style="display:flex; gap:4px;">
                        @foreach(['all'=>'All','pending'=>'Pending','accepted'=>'Accepted','dismissed'=>'Dismissed'] as $key => $label)
                            <a href="{{ route('web.ai', array_merge(request()->query(), ['type'=>$activeType, 'status'=>$key])) }}"
                               style="
                                   font-size:11px; padding:4px 9px; border-radius:6px;
                                   text-decoration:none; font-weight:500;
                                   border:1px solid {{ $activeStatus===$key ? 'rgba(255,255,255,.12)' : 'transparent' }};
                                   background:{{ $activeStatus===$key ? 'rgba(255,255,255,.06)' : 'transparent' }};
                                   color:{{ $activeStatus===$key ? '#e2e3df' : '#44474c' }};
                                   transition:all .15s;
                               ">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Cards list --}}
                <div style="flex:1; overflow-y:auto; padding:14px;">
                    @forelse($results as $index => $result)
                        @php
                            $isSelected = isset($selected) && $selected->id === $result->id;
                            $statusClass = match($result->status) {
                                'accepted'  => 'chip-accepted',
                                'dismissed' => 'chip-dismissed',
                                default     => 'chip-pending',
                            };
                            $typeColor = match($result->result_type) {
                                'soap'         => ['bg'=>'rgba(173,198,255,.1)',  'border'=>'rgba(173,198,255,.3)', 'color'=>'#adc6ff', 'icon'=>'ti-clipboard-list'],
                                'xray'         => ['bg'=>'rgba(79,219,204,.1)',   'border'=>'rgba(79,219,204,.3)',  'color'=>'#4fdbcc', 'icon'=>'ti-scan'],
                                'prescription' => ['bg'=>'rgba(232,168,56,.1)',   'border'=>'rgba(232,168,56,.3)',  'color'=>'#e8a838', 'icon'=>'ti-pill'],
                                'perio'        => ['bg'=>'rgba(255,180,171,.1)',  'border'=>'rgba(255,180,171,.3)','color'=>'#ffb4ab', 'icon'=>'ti-activity'],
                                default        => ['bg'=>'rgba(255,255,255,.05)', 'border'=>'rgba(255,255,255,.1)', 'color'=>'#8e9196', 'icon'=>'ti-sparkles'],
                            };
                        @endphp
                        <div class="result-card reveal reveal-{{ min($index+1,5) }} {{ $isSelected ? 'selected' : '' }}"
                             hx-get="{{ route('web.ai.show', $result->id) }}"
                             hx-target="#detail-panel"
                             hx-swap="innerHTML"
                             onclick="document.querySelectorAll('.result-card').forEach(c=>c.classList.remove('selected'));this.classList.add('selected')">

                            <div style="display:flex; align-items:flex-start; gap:11px; margin-bottom:10px;">
                                {{-- Type icon --}}
                                <div style="
                                    width:34px; height:34px; border-radius:9px; flex-shrink:0;
                                    display:flex; align-items:center; justify-content:center;
                                    font-size:17px;
                                    background:{{ $typeColor['bg'] }}; border:1px solid {{ $typeColor['border'] }}; color:{{ $typeColor['color'] }};
                                ">
                                    <i class="ti {{ $typeColor['icon'] }}"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:13px; font-weight:600; color:#e2e3df; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $result->title }}
                                    </div>
                                    <div style="font-size:11px; color:#44474c; margin-bottom:7px; font-family:'JetBrains Mono',monospace;">
                                        {{ $result->patient?->full_name ?? 'Unknown patient' }} · #P-{{ str_pad($result->patient_id, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div style="font-size:12px; color:#8e9196; line-height:1.55; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                        {{ $result->summary }}
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; padding-top:10px; border-top:1px solid rgba(255,255,255,.03);">
                                <span style="font-size:11px; color:#44474c; font-family:'JetBrains Mono',monospace;">
                                    {{ $result->created_at->isToday() ? 'Today ' . $result->created_at->format('H:i') : $result->created_at->format('M j H:i') }}
                                </span>
                                <span class="{{ $statusClass }}" style="font-size:10px; padding:2px 7px; border-radius:4px; font-weight:600; text-transform:uppercase; letter-spacing:.04em;">
                                    {{ ucfirst($result->status) }}
                                </span>
                                <span style="margin-left:auto; font-size:10px; color:#44474c; font-family:'JetBrains Mono',monospace;">
                                    {{ $result->ai_model ?? 'gemini-2.0-flash' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; gap:12px; color:#44474c;">
                            <i class="ti ti-robot" style="font-size:40px; opacity:.3;"></i>
                            <p style="font-size:13px; margin:0;">No AI results found for this filter.</p>
                        </div>
                    @endforelse

                    {{-- Pagination --}}
                    @if($results->hasPages())
                        <div style="padding-top:10px;">
                            {{ $results->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </section>

            {{-- ─── Detail panel ────────────────────────────── --}}
            <section id="detail-panel" style="
                width:340px; flex-shrink:0; display:flex; flex-direction:column; overflow:hidden;
                background:rgba(10,16,14,.60); backdrop-filter:blur(20px);
                border:1px solid rgba(255,255,255,.07); border-radius:16px;
                box-shadow:0 20px 50px rgba(0,0,0,.3);
            ">
                @if(isset($selected))
                    @include('web.ai._detail', ['result' => $selected])
                @else
                    {{-- Empty state --}}
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; gap:14px; padding:40px; text-align:center;">
                        <svg class="tooth-glow" width="48" height="56" viewBox="0 0 68 104" fill="none">
                            <path d="M0 28C0 10 8 0 20 0C26 0 30 4 34 4C38 4 42 0 48 0C60 0 68 10 68 28L60 58C58 66 52 68 48 68C44 68 40 64 34 64C28 64 24 68 20 68C16 68 10 66 8 58Z" stroke="#4fdbcc" stroke-width="3" fill="none" stroke-opacity=".4"/>
                            <path d="M14 68C12 80 14 96 18 100" stroke="#4fdbcc" stroke-width="3" stroke-linecap="round" fill="none" stroke-opacity=".4"/>
                            <path d="M54 68C56 80 54 96 50 100" stroke="#4fdbcc" stroke-width="3" stroke-linecap="round" fill="none" stroke-opacity=".4"/>
                        </svg>
                        <p style="font-size:13px; color:#44474c; margin:0;">Select a result to review</p>
                    </div>
                @endif
            </section>

        </div>{{-- /ai-layout --}}
    </div>{{-- /page shell --}}

    @push('scripts')
    <script>
    // Card hover — keyboard accessible
    document.querySelectorAll('.result-card').forEach(card => {
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        });
    });
    </script>
    @endpush

</x-layouts.app>
