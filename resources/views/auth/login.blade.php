<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - e-Docking Control System</title>
    @vite(['resources/css/style.css', 'resources/js/app.js'])
</head>
<body style="min-height:100vh;margin:0;background:
        linear-gradient(rgba(15,23,42,0.5), rgba(15,23,42,0.5)),
        url('{{ asset('img/bg_login.jpg') }}') center center / cover no-repeat fixed;">
    <div class="st-minh-screen st-flex st-items-center st-justify-center">
        <div class="st-card st-w-full st-maxw-420 st-p-18">
            <div class="st-flex st-justify-center st-items-center st-mb-16">
                <img src="{{ asset('img/e-Docking Control System.png') }}" alt="e-DCS" class="st-login-logo">
            </div>

            <div class="st-text-center">
                <div class="st-text-16 st-font-bold">Sign in</div>
                <div class="st-text--sm st-text--muted">Please sign in to continue</div>
            </div>

            @php
                $isLocked = $errors->any() && (str_contains($errors->first(), 'locked') || str_contains($errors->first(), 'Account locked'));
            @endphp

            @if ($errors->any() && ! $isLocked)
                <div class="st-alert st-alert--error st-mb-1">
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                </div>
            @endif

            @if ($isLocked)
                <div class="st-alert st-alert--warning st-alert--lock st-mb-12 st-flex st-items-start st-gap-8">
                    <div class="st-alert__icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="st-flex-1">
                        <div class="st-font-semibold st-mb-4">Account locked</div>
                        <div class="st-text--sm st-mb-6 st-text--slate-600">
                            Too many failed login attempts. To regain access, please request a password reset.
                        </div>
                        <a href="{{ route('forgot-password') }}" class="st-btn st-btn--sm st-btn--lock-reset st-justify-center st-inline-flex">
                            Request password reset
                        </a>
                    </div>
                </div>
            @endif

            @unless ($isLocked)
                <form method="POST" action="{{ route('login.store') }}" class="st-flex st-flex-col st-gap-10">
                    @csrf

                    <div class="st-form-field">
                        <label class="st-label" for="login">Email / NIK / Username</label>
                        <input type="text" class="st-input" id="login" name="login" value="{{ old('login') }}" required autofocus>
                    </div>

                    <div class="st-form-field">
                        <label class="st-label" for="password">Password</label>
                        <input type="password" class="st-input" id="password" name="password" required>
                    </div>

                    <button type="submit" class="st-btn st-w-full st-justify-center">Sign in</button>
                </form>
            @endunless

            <div class="st-mt-12 st-text-center st-text--sm st-text--muted">&copy; {{ date('Y') }} e-Docking Control System</div>
        </div>
    </div>
</body>
</html>
