<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - e-Docking Control System</title>
    @vite(['resources/css/style.css', 'resources/js/app.js'])
</head>
<body>
    <div class="st-minh-screen st-flex st-items-center st-justify-center">
        <div class="st-card st-w-full st-maxw-420 st-p-18">
            <div class="st-flex st-justify-center st-items-center st-mb-15 st-minh-70">
                <img src="{{ asset('img/logo-full.png') }}" alt="Oneject" class="st-h-auto st-w-auto st-maxw-250 st-object-contain">
            </div>

            <div class="st-text-center st-mb-14">
                <div class="st-text-16 st-font-bold">Password Reset Request</div>
                <div class="st-text--sm st-text--muted">Contact administrator for password assistance</div>
            </div>

            @if (session('success'))
                <div class="st-alert st-alert--success st-alert--autodismiss st-mb-1">
                    <span class="st-alert__icon"><i class="fa-solid fa-circle-check"></i></span>
                    <span class="st-alert__text">{{ session('success') }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            @if (session('error'))
                <div class="st-alert st-alert--error st-alert--autodismiss st-mb-1">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ session('error') }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="st-alert st-alert--error st-alert--autodismiss st-mb-1">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ $errors->first() }}</span>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            <form method="POST" action="{{ route('forgot-password.send') }}" class="st-flex st-flex-col st-gap-10">
                @csrf

                <div class="st-form-field">
                    <label class="st-label" for="login">Email, Username, or NIK</label>
                    <input type="text" class="st-input" id="login" name="login" value="{{ old('login') }}" required autofocus>
                </div>

                <div class="st-form-field">
                    <label class="st-label" for="reason">Reason for Password Reset</label>
                    <textarea id="reason" name="reason" rows="3" class="st-input st-input--textarea" required>{{ old('reason') }}</textarea>
                </div>

                <button type="submit" class="st-btn st-w-full st-justify-center">Send Reset Request</button>
            </form>

            <div class="st-mt-12 st-text-center st-text--sm">
                <a href="{{ route('login') }}" class="st-text--primary st-underline">Back to Login</a>
            </div>

            <div class="st-mt-12 st-text-center st-text--sm st-text--muted">&copy; {{ date('Y') }} e-Docking Control System</div>
        </div>
    </div>
</body>
</html>
