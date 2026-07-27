@props(['pageTitle' => ''])

<header style="
    height: 58px;
    background: rgba(10,16,14,.55);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255,255,255,.06);
    display: flex;
    align-items: center;
    padding: 0 24px;
    gap: 16px;
    flex-shrink: 0;
    z-index: 10;
">
    {{-- Page title --}}
    <h1 style="font-size:16px; font-weight:600; color:#e2e3df; flex:1; margin:0; letter-spacing:-.01em;">
        {{ $pageTitle }}
    </h1>

    {{-- Search --}}
    <div style="position:relative; width:240px;">
        <i class="ti ti-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:15px; color:#44474c; pointer-events:none;"></i>
        <input
            type="text"
            placeholder="Search patients, records…"
            style="
                width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07);
                border-radius:10px; padding:7px 12px 7px 34px; font-size:12px; color:#e2e3df;
                outline:none; font-family:'Inter',sans-serif; transition:border-color .15s;
            "
            onfocus="this.style.borderColor='rgba(79,219,204,.35)'"
            onblur="this.style.borderColor='rgba(255,255,255,.07)'"
        >
    </div>

    {{-- Notification btn --}}
    <div style="position:relative;">
        <button style="
            width:34px; height:34px; border-radius:9px; background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.07); display:flex; align-items:center;
            justify-content:center; cursor:pointer; color:#8e9196; font-size:16px; transition:all .15s;
        "
        onmouseover="this.style.background='rgba(255,255,255,.08)';this.style.color='#e2e3df'"
        onmouseout="this.style.background='rgba(255,255,255,.04)';this.style.color='#8e9196'">
            <i class="ti ti-bell"></i>
        </button>
        <span class="notif-pulse" style="
            position:absolute; top:8px; right:8px;
            width:7px; height:7px; background:#4fdbcc; border-radius:50%;
            border:1.5px solid #0c0f0d;
        "></span>
    </div>

    {{-- + Appointment CTA --}}
    <a href="{{ route('web.appointments.create') }}"
       class="btn-bloom"
       style="
           display:flex; align-items:center; gap:6px; padding:7px 14px;
           background:linear-gradient(135deg, #4fdbcc, #0da291);
           border-radius:10px; text-decoration:none; color:#050e1f;
           font-size:12px; font-weight:700; border:none; cursor:pointer;
           transition:transform .15s;
       "
       onmouseover="this.style.transform='translateY(-1px)'"
       onmouseout="this.style.transform='translateY(0)'">
        <i class="ti ti-plus" style="font-size:14px;"></i>
        Appointment
    </a>
</header>
