<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - e-Docking Control System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/style.css', 'resources/js/app.js'])
</head>
<body class="st-auth-bg" style="--st-auth-bg-url: url('{{ asset('img/bg_login.jpg') }}');">
    <div class="st-minh-screen st-flex st-items-center st-justify-center">
        <div class="st-card st-w-full st-maxw-420 st-p-18">
            <div class="st-flex st-justify-center st-items-center st-mb-8">
                <img src="{{ asset('img/logo-full.png') }}" alt="e-DCS" class="st-login-logo">
            </div>

            <div class="st-text-center">
                <div class="st-text-16 st-font-bold">Sign in</div>
                <div class="st-text--sm st-text--muted">Please sign in to continue</div>
            </div>
            <br>
            @php
                $isLocked = $errors->any() && (str_contains($errors->first(), 'locked') || str_contains($errors->first(), 'Account locked'));
            @endphp

            @if ($errors->any() && ! $isLocked)
                <div class="st-alert st-alert--error st-alert--autodismiss st-mb-1">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
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
                        <div class="st-input-wrap">
                            <input type="password" class="st-input st-input--pr-40" id="password" name="password" required>
                            <button type="button" class="btn-toggle-password st-btn-toggle-password" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="st-btn st-w-full st-justify-center">Sign in</button>
                </form>
            @endunless

            <div class="st-mt-12 st-text-center st-text--sm st-text--muted">&copy; {{ date('Y') }} e-Docking Control System</div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>
</body>
</html>
