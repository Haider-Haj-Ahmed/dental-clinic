<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Crystalline Dental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @vite(['resources/css/app.css'])
    <style>
        body {
            display:flex; align-items:center; justify-content:center;
            min-height:100vh; margin:0;
        }
        .login-card {
            width:100%; max-width:400px; padding:40px;
            background:rgba(15,23,20,.65);
            backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px);
            border:1px solid rgba(255,255,255,.08);
            border-radius:20px;
            box-shadow:0 30px 80px rgba(0,0,0,.4);
        }
        .input-field {
            width:100%; background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.09); border-radius:10px;
            padding:11px 14px; font-size:14px; color:#e2e3df;
            outline:none; font-family:'Inter',sans-serif;
            transition:border-color .15s; box-sizing:border-box;
        }
        .input-field:focus { border-color:rgba(79,219,204,.4); }
        .input-field::placeholder { color:#44474c; }
        .btn-login {
            width:100%; padding:12px; border-radius:10px; border:none;
            background:linear-gradient(135deg,#4fdbcc,#0da291);
            color:#050e1f; font-size:14px; font-weight:700;
            cursor:pointer; font-family:'Inter',sans-serif;
            transition:all .2s; box-shadow:0 4px 16px rgba(79,219,204,.3);
        }
        .btn-login:hover { transform:translateY(-1px); box-shadow:0 6px 22px rgba(79,219,204,.45); }
        label { font-size:12px; color:#8e9196; font-weight:500; display:block; margin-bottom:6px; }
        .error-msg { font-size:12px; color:#ffb4ab; margin-top:4px; }
    </style>
</head>
<body>
<div class="login-card reveal">

    {{-- Logo --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:36px;">
        <svg class="tooth-glow" width="30" height="34" viewBox="0 0 68 104" fill="none">
            <path d="M0 28C0 10 8 0 20 0C26 0 30 4 34 4C38 4 42 0 48 0C60 0 68 10 68 28L60 58C58 66 52 68 48 68C44 68 40 64 34 64C28 64 24 68 20 68C16 68 10 66 8 58Z" stroke="#4fdbcc" stroke-width="4" fill="none"/>
            <path d="M14 68C12 80 14 96 18 100" stroke="#4fdbcc" stroke-width="4" stroke-linecap="round" fill="none"/>
            <path d="M54 68C56 80 54 96 50 100" stroke="#4fdbcc" stroke-width="4" stroke-linecap="round" fill="none"/>
        </svg>
        <div>
            <div style="font-size:18px;font-weight:700;color:#e2e3df;letter-spacing:-.01em;">Crystalline</div>
            <div style="font-size:10px;font-weight:600;color:#4fdbcc;text-transform:uppercase;letter-spacing:.1em;">Dental PMS</div>
        </div>
    </div>

    <div style="margin-bottom:28px;">
        <h1 style="font-size:22px;font-weight:700;color:#e2e3df;margin:0 0 4px;letter-spacing:-.01em;">Welcome back</h1>
        <p style="font-size:13px;color:#44474c;margin:0;">Sign in to your clinic dashboard</p>
    </div>

    {{-- Error alert --}}
    @if($errors->any())
        <div style="background:rgba(255,180,171,.08);border:1px solid rgba(147,0,10,.35);border-radius:10px;padding:11px 14px;margin-bottom:20px;font-size:13px;color:#ffb4ab;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-alert-circle"></i>{{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" style="display:flex;flex-direction:column;gap:18px;">
        @csrf

        <div>
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email"
                   value="{{ old('email', 'owner@clinic.local') }}"
                   class="input-field" required autofocus>
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password"
                   class="input-field" required>
        </div>

        <div style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="remember" id="remember" style="accent-color:#4fdbcc;">
            <label for="remember" style="margin:0;font-size:12px;color:#8e9196;cursor:pointer;">Remember me</label>
        </div>

        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,.05);font-size:11px;color:#44474c;text-align:center;font-family:'JetBrains Mono',monospace;">
        CRYSTALLINE ENGINE v4.8.2 · SYSTEM ONLINE
    </div>
</div>
</body>
</html>
