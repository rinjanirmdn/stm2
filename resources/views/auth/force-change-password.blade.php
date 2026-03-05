<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - e-Docking Control System</title>
    @vite(['resources/css/style.css', 'resources/js/app.js'])
</head>
<body style="min-height:100vh;margin:0;background:
        linear-gradient(rgba(15,23,42,0.5), rgba(15,23,42,0.5)),
        url('{{ asset('img/bg_login.jpg') }}') center center / cover no-repeat fixed;">
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
                    <input type="password" class="st-input" id="new_password" name="new_password" required autofocus>
                </div>

                <div class="st-form-field">
                    <label class="st-label" for="new_password_confirmation">Confirm New Password</label>
                    <input type="password" class="st-input" id="new_password_confirmation" name="new_password_confirmation" required>
                </div>

                <button type="submit" class="st-btn st-w-full st-justify-center">Set Password</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="st-mt-8 st-text-center">
                @csrf
                <button type="submit" class="st-text--sm st-text-brand-500 st-font-semibold" style="background:none; border:none; cursor:pointer;">
                    Logout instead
                </button>
            </form>

            <div class="st-mt-12 st-text-center st-text--sm st-text--muted">&copy; {{ date('Y') }} e-Docking Control System</div>
        </div>
    </div>
<script src="https://kit.fontawesome.com/9dc370e5b7.js" crossorigin="anonymous"></script>
</body>
</html>
