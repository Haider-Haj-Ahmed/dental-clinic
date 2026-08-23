@props([
    'pageTitle' => '',
    'activeRoute' => '',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ? $pageTitle.' - ' : '' }}{{ config('app.name', 'Crystalline Dental') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://unpkg.com/htmx.org@1.9.12" defer></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-screen overflow-hidden">
<div class="flex h-screen overflow-hidden">
    <x-sidebar :active-route="$activeRoute ?: (Route::currentRouteName() ?? '')" />

    <div class="flex flex-col flex-1 overflow-hidden min-w-0">
        <x-topbar :page-title="$pageTitle" />

        @if(session('success'))
            <div id="flash" style="position:fixed;top:70px;right:20px;z-index:50;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:500;background:rgba(79,219,204,.12);border:1px solid rgba(79,219,204,.3);color:#4fdbcc;animation:fadeSlideUp .3s ease forwards;">
                <i class="ti ti-check" style="margin-right:6px;"></i>{{ session('success') }}
            </div>
            <script>setTimeout(()=>document.getElementById('flash')?.remove(), 3500)</script>
        @endif

        @if(session('error') || $errors->any())
            <div id="flash-err" style="position:fixed;top:70px;right:20px;z-index:50;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:500;background:rgba(255,180,171,.08);border:1px solid rgba(147,0,10,.4);color:#ffb4ab;animation:fadeSlideUp .3s ease forwards;">
                <i class="ti ti-alert-circle" style="margin-right:6px;"></i>{{ session('error') ?? $errors->first() }}
            </div>
            <script>setTimeout(()=>document.getElementById('flash-err')?.remove(), 4000)</script>
        @endif

        <main class="flex-1 overflow-hidden">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
