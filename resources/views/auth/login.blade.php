<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Slot Time Management</title>
    @vite(['resources/css/app.css', 'resources/css/style.css', 'resources/js/app.js'])
</head>
<body>
    <div class="st-minh-screen st-flex st-items-center st-justify-center st-p-20">
        <div class="st-card st-w-full st-maxw-420 st-p-18">
            <div class="st-flex st-justify-center st-items-center st-mb-1 st-minh-70">
                <img src="{{ asset('img/logo-full.png') }}" alt="Slot Time" class="st-h-70 st-object-contain st-w-full">
            </div>

            <div class="st-text-center st-mb-1">
                <div class="st-text-16 st-font-bold">Sign in</div>
                <div class="st-text--sm st-text--muted">Please sign in to continue</div>
            </div>

            @if ($errors->any())
                <div class="st-alert st-alert--error st-mb-1">
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="st-flex st-flex-col st-gap-10">
                @csrf

                <div class="st-form-field">
                    <label class="st-label" for="nik">NIK/Username</label>
                    <input type="text" class="st-input" id="nik" name="nik" value="{{ old('nik') }}" required autofocus>
                </div>

                <div class="st-form-field">
                    <label class="st-label" for="password">Password</label>
                    <input type="password" class="st-input" id="password" name="password" required>
                </div>

                <button type="submit" class="st-btn st-w-full st-justify-center">Sign in</button>
            </form>

            <div class="st-mt-12 st-text-center st-text--sm st-text--muted">&copy; {{ date('Y') }} Slot Time Management</div>
        </div>
    </div>
</body>
</html>
