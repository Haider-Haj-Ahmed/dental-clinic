@props(['activeRoute' => ''])

@php
/**
 * Sidebar component.
 *
 * Props:
 *   activeRoute (string) — current named route, e.g. 'web.ai.index'
 *
 * Usage:
 *   <x-sidebar :active-route="Route::currentRouteName()" />
 */
$nav = [
    'Main' => [
        ['route' => 'web.dashboard',    'icon' => 'ti-layout-dashboard', 'label' => 'Dashboard'],
        ['route' => 'web.appointments', 'icon' => 'ti-calendar',         'label' => 'Appointments', 'badge' => auth()->user()?->todayAppointmentsCount ?? 0,  'badge_type' => 'teal'],
        ['route' => 'web.patients',     'icon' => 'ti-users',            'label' => 'Patients'],
        ['route' => 'web.recalls',      'icon' => 'ti-bell-ringing',     'label' => 'Recalls',      'badge' => auth()->user()?->pendingRecallsCount ?? 0,      'badge_type' => 'warn'],
    ],
    'Clinical' => [
        ['route' => 'web.encounters',      'icon' => 'ti-clipboard-list', 'label' => 'Encounters'],
        ['route' => 'web.odontogram',      'icon' => 'ti-tooth',          'label' => 'Odontogram'],
        ['route' => 'web.perio',           'icon' => 'ti-activity',       'label' => 'Perio exams'],
        ['route' => 'web.treatment-plans', 'icon' => 'ti-notes',          'label' => 'Treatment plans'],
        ['route' => 'web.prescriptions',   'icon' => 'ti-pill',           'label' => 'Prescriptions'],
    ],
    'Finance' => [
        ['route' => 'web.invoices', 'icon' => 'ti-receipt',     'label' => 'Invoices'],
        ['route' => 'web.payments', 'icon' => 'ti-credit-card', 'label' => 'Payments'],
    ],
    'Operations' => [
        ['route' => 'web.inventory', 'icon' => 'ti-box',       'label' => 'Inventory'],
        ['route' => 'web.reports',   'icon' => 'ti-chart-bar', 'label' => 'Reports'],
        ['route' => 'web.ai',        'icon' => 'ti-sparkles',  'label' => 'AI assistant'],
    ],
];
@endphp

<aside style="
    width: 220px;
    flex-shrink: 0;
    background: rgba(10,16,14,0.72);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-right: 1px solid rgba(255,255,255,.06);
    display: flex;
    flex-direction: column;
    z-index: 20;
">
    {{-- ── Logo ─────────────────────────────────────── --}}
    <div style="padding: 22px 18px 16px; border-bottom: 1px solid rgba(255,255,255,.05);">
        <a href="{{ route('web.dashboard') }}" style="display:flex; align-items:center; gap:10px; text-decoration:none;">

            {{-- Animated tooth SVG (transparent bg, no box) --}}
            <svg class="tooth-glow" width="28" height="32" viewBox="0 0 68 104" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                <path d="M0 28C0 10 8 0 20 0C26 0 30 4 34 4C38 4 42 0 48 0C60 0 68 10 68 28L60 58C58 66 52 68 48 68C44 68 40 64 34 64C28 64 24 68 20 68C16 68 10 66 8 58Z"
                      stroke="#4fdbcc" stroke-width="4" fill="none"/>
                <path d="M14 68C12 80 14 96 18 100" stroke="#4fdbcc" stroke-width="4" stroke-linecap="round" fill="none"/>
                <path d="M54 68C56 80 54 96 50 100" stroke="#4fdbcc" stroke-width="4" stroke-linecap="round" fill="none"/>
                <path d="M34 4V64" stroke="rgba(79,219,204,.25)" stroke-width="2" stroke-dasharray="4 4"/>
            </svg>

            <div>
                <div style="font-size:13px; font-weight:700; color:#e2e3df; letter-spacing:-.01em; line-height:1.2;">Crystalline</div>
                <div style="font-size:9px; font-weight:600; color:#4fdbcc; text-transform:uppercase; letter-spacing:.08em;">Dental PMS</div>
            </div>
        </a>
    </div>

    {{-- ── Navigation ──────────────────────────────── --}}
    <nav style="flex:1; padding:10px 10px; overflow-y:auto;">
        @foreach($nav as $section => $items)
            <div style="font-size:9px; color:rgba(196,198,204,.35); text-transform:uppercase; letter-spacing:.08em; padding:14px 10px 5px; font-weight:700; font-family:'JetBrains Mono',monospace;">
                {{ $section }}
            </div>
            @foreach($items as $item)
                @php
                    $isActive = str_starts_with($activeRoute, $item['route']);
                    $badge    = $item['badge'] ?? 0;
                    $badgeType = $item['badge_type'] ?? 'teal';
                @endphp
                <a href="{{ route($item['route']) }}"
                   style="
                       display:flex; align-items:center; gap:9px;
                       padding:9px 10px; border-radius:8px; margin-bottom:2px;
                       font-size:13px; font-weight:{{ $isActive ? '500' : '400' }};
                       text-decoration:none; transition:all .18s ease;
                       color:{{ $isActive ? '#4fdbcc' : '#8e9196' }};
                       background:{{ $isActive ? 'rgba(79,219,204,.09)' : 'transparent' }};
                       border:1px solid {{ $isActive ? 'rgba(79,219,204,.18)' : 'transparent' }};
                   "
                   onmouseover="if(!{{ $isActive ? 'true' : 'false' }})this.style.background='rgba(255,255,255,.04)';this.style.color='#e2e3df'"
                   onmouseout="if(!{{ $isActive ? 'true' : 'false' }})this.style.background='transparent';if(!{{ $isActive ? 'true' : 'false' }})this.style.color='#8e9196'">
                    <i class="ti {{ $item['icon'] }}" style="font-size:16px; width:18px; text-align:center; flex-shrink:0;"></i>
                    <span style="flex:1;">{{ $item['label'] }}</span>
                    @if($badge > 0)
                        <span style="
                            font-size:10px; padding:1px 6px; border-radius:10px; font-weight:600;
                            background:{{ $badgeType === 'warn' ? 'rgba(232,168,56,.15)' : 'rgba(79,219,204,.15)' }};
                            color:{{ $badgeType === 'warn' ? '#e8a838' : '#4fdbcc' }};
                            border:1px solid {{ $badgeType === 'warn' ? 'rgba(232,168,56,.2)' : 'rgba(79,219,204,.2)' }};
                        ">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- ── User card ────────────────────────────────── --}}
    <div style="padding:12px; border-top:1px solid rgba(255,255,255,.05);">
        <div style="display:flex; align-items:center; gap:9px; padding:8px 10px; border-radius:10px; background:rgba(255,255,255,.03);">
            {{-- Avatar --}}
            <div style="
                width:30px; height:30px; border-radius:8px; flex-shrink:0;
                background:linear-gradient(135deg,#0f172a,#1e293b);
                border:1px solid rgba(79,219,204,.4);
                display:flex; align-items:center; justify-content:center;
                font-size:11px; font-weight:700; color:#4fdbcc; font-family:'JetBrains Mono',monospace;
            ">
                {{ strtoupper(substr(auth()->user()?->name ?? 'DR', 0, 2)) }}
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:11px; font-weight:600; color:#e2e3df; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ auth()->user()?->name ?? 'Dr. Haider Ahmed' }}
                </div>
                <div style="font-size:9px; color:#44474c; text-transform:uppercase; letter-spacing:.05em;">
                    {{ auth()->user()?->role ?? 'Provider' }}
                </div>
            </div>
            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0;">
                @csrf
                <button type="submit" title="Sign out"
                    style="background:none; border:none; cursor:pointer; color:#44474c; font-size:15px; padding:2px; display:flex; align-items:center; transition:color .15s;"
                    onmouseover="this.style.color='#ffb4ab'"
                    onmouseout="this.style.color='#44474c'">
                    <i class="ti ti-logout"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
