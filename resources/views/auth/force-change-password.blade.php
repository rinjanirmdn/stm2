<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - e-Docking Control System</title>
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
                <div class="st-text-16 st-font-bold">Change Default Password</div>
                <div class="st-text--sm st-text--muted st-mb-4">For security reasons, you must change your password before continuing.</div>
            </div>
            <br>
            
            @if ($errors->any())
                <div class="st-alert st-alert--error st-alert--autodismiss st-mb-4">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            <form method="POST" action="{{ route('password.force-change.store') }}" class="st-flex st-flex-col st-gap-10">
                @csrf

                <div class="st-form-field">
                    <label class="st-label" for="new_password">New Password</label>
                    <div class="st-password-wrap">
                        <input type="password" class="st-input" id="new_password" name="new_password" required autofocus>
                        <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="st-password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="st-form-field">
                    <label class="st-label" for="new_password_confirmation">Confirm New Password</label>
                    <div class="st-password-wrap">
                        <input type="password" class="st-input" id="new_password_confirmation" name="new_password_confirmation" required>
                        <button type="button" onclick="togglePasswordVisibility('new_password_confirmation', this)" class="st-password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="st-btn st-w-full st-justify-center">Set Password</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="st-mt-8 st-text-center">
                @csrf
                <button type="submit" class="st-text--sm st-text-brand-500 st-font-semibold st-link-btn">
                    Logout instead
                </button>
            </form>

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
