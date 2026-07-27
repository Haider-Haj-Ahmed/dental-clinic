<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Crystalline Dental PMS' }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        surface:          '#111412',
                        'surface-low':    '#1a1c1a',
                        'surface-mid':    '#1e201e',
                        'surface-high':   '#282b28',
                        'surface-var':    '#333533',
                        'on-surface':     '#e2e3df',
                        'on-surface-var': '#c4c6cc',
                        outline:          '#8e9196',
                        'outline-var':    '#44474c',
                        primary:          '#bac8dc',
                        secondary:        '#4fdbcc',
                        tertiary:         '#adc6ff',
                        error:            '#ffb4ab',
                        'error-container':'#93000a',
                    },
                    fontFamily: {
                        sans:  ['Inter', 'system-ui', 'sans-serif'],
                        mono:  ['JetBrains Mono', 'monospace'],
                    },
                    borderRadius: {
                        sm:  '0.25rem',
                        DEFAULT: '0.5rem',
                        md:  '0.75rem',
                        lg:  '1rem',
                        xl:  '1.5rem',
                        full:'9999px',
                    },
                    backdropBlur: {
                        xs: '4px',
                        sm: '8px',
                        DEFAULT: '16px',
                        lg: '24px',
                        xl: '32px',
                    },
                }
            }
        }
    </script>

    <style>
        /* ── Base ───────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #0c0f0d;
            background-image:
                radial-gradient(ellipse 60% 40% at 15% 20%, rgba(79,219,204,.06) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 85% 80%, rgba(173,198,255,.04) 0%, transparent 60%);
            color: #e2e3df;
            height: 100vh;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Glass utility ──────────────────────────────── */
        .glass {
            background: rgba(15, 23, 20, 0.55);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,.07);
        }

        .glass-deep {
            background: rgba(10, 16, 14, 0.70);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,.06);
        }

        /* ── Custom scrollbar ───────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.07); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.13); }

        /* ── Scroll-reveal animation ────────────────────── */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .reveal {
            opacity: 0;
            animation: fadeSlideUp .45s cubic-bezier(.4,0,.2,1) forwards;
        }
        .reveal-1 { animation-delay: .05s; }
        .reveal-2 { animation-delay: .12s; }
        .reveal-3 { animation-delay: .19s; }
        .reveal-4 { animation-delay: .26s; }
        .reveal-5 { animation-delay: .33s; }

        /* ── Glow pulse for tooth logo ──────────────────── */
        @keyframes toothPulse {
            0%, 100% { filter: drop-shadow(0 0 6px rgba(79,219,204,.45)); }
            50%       { filter: drop-shadow(0 0 14px rgba(79,219,204,.80)); }
        }
        .tooth-glow { animation: toothPulse 3s ease-in-out infinite; }

        /* ── Teal bloom on primary btn ──────────────────── */
        @keyframes bloom {
            0%   { box-shadow: 0 4px 15px rgba(79,219,204,.25); }
            50%  { box-shadow: 0 4px 28px rgba(79,219,204,.55); }
            100% { box-shadow: 0 4px 15px rgba(79,219,204,.25); }
        }
        .btn-bloom { animation: bloom 2.5s ease-in-out infinite; }

        /* ── Notification dot pulse ─────────────────────── */
        @keyframes dotPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(79,219,204,.6); }
            50%       { box-shadow: 0 0 0 5px rgba(79,219,204,.0); }
        }
        .notif-pulse { animation: dotPulse 2s ease-in-out infinite; }
    </style>

    {{-- Per-page styles slot --}}
    @stack('styles')
</head>
<body>

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar component --}}
    <x-sidebar :active-route="$activeRoute ?? ''" />

    {{-- Main canvas --}}
    <div class="flex flex-col flex-1 overflow-hidden min-w-0">

        {{-- Top bar component --}}
        <x-topbar :page-title="$pageTitle ?? ''" />

        {{-- Page content --}}
        <main class="flex-1 overflow-hidden">
            {{ $slot }}
        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>
