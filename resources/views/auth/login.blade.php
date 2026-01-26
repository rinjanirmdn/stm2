<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Slot Time Management</title>
    @vite(['resources/css/app.css', 'resources/css/style.css', 'resources/js/app.js'])
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div class="st-card" style="width:100%;max-width:420px;padding:18px;">
            <div style="display:flex;justify-content:center;margin-bottom:1px;min-height:70px;align-items:center;">
                <img src="{{ asset('img/logo-full.png') }}" alt="Slot Time" style="height:70px;object-fit:contain;max-width:100%;">
            </div>

            <div style="text-align:center;margin-bottom:5px;">
                <div style="font-size:16px;font-weight:700;">Sign in</div>
                <div style="font-size:12px;color:#6b7280;">Please sign in to continue</div>
            </div>

            @if ($errors->any())
                <div class="st-alert st-alert--error" style="margin-bottom:1px;">
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" style="display:flex;flex-direction:column;gap:10px;">
                @csrf

                <div class="st-form-field">
                    <label class="st-label" for="nik">NIK/Username</label>
                    <input type="text" class="st-input" id="nik" name="nik" value="{{ old('nik') }}" required autofocus>
                </div>

                <div class="st-form-field">
                    <label class="st-label" for="password">Password</label>
                    <input type="password" class="st-input" id="password" name="password" required>
                </div>

                <button type="submit" class="st-btn" style="width:100%;justify-content:center;">Sign in</button>
            </form>

            <div style="margin-top:12px;text-align:center;font-size:12px;color:#6b7280;">&copy; {{ date('Y') }} Slot Time Management</div>
        </div>
    </div>
</body>
</html>
