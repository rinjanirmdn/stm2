<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - e-Docking Control System</title>
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
                <div class="st-text-16 st-font-bold">Reset Your Password</div>
                <div class="st-text--sm st-text--muted st-mb-4">Enter your new password below</div>
            </div>
            <br>

            @if (session('error'))
                <div class="st-alert st-alert--error st-alert--autodismiss st-mb-4">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ session('error') }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="st-alert st-alert--error st-alert--autodismiss st-mb-4">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset.store') }}" class="st-flex st-flex-col st-gap-10">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="st-form-field">
                    <label class="st-label" for="password">New Password</label>
                    <div class="st-password-wrap">
                        <input type="password" class="st-input" id="password" name="password" required autofocus minlength="6">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" class="st-password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="st-form-field">
                    <label class="st-label" for="password_confirmation">Confirm New Password</label>
                    <div class="st-password-wrap">
                        <input type="password" class="st-input" id="password_confirmation" name="password_confirmation" required minlength="6">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="st-password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="st-btn st-w-full st-justify-center">Reset Password</button>
            </form>

            <div class="st-mt-12 st-text-center st-text--sm">
                <a href="{{ route('login') }}" class="st-text--primary st-underline">Back to Login</a>
            </div>

            <div class="st-mt-12 st-text-center st-text--sm st-text--muted">&copy; {{ date('Y') }} e-Docking Control System</div>
        </div>
    </div>
<script>
    function togglePasswordVisibility(inputId, btn) {
        var input = document.getElementById(inputId);
        var icon = btn.querySelector('i');
        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
</body>
</html>
