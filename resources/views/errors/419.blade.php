<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired - e-Docking Control System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #1a1a2e;
        }
        .container {
            text-align: center;
            background: #fff;
            border-radius: 16px;
            padding: 48px 32px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1a1a2e;
        }
        p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .countdown {
            font-size: 14px;
            color: #9ca3af;
            margin-bottom: 20px;
        }
        .countdown span {
            font-weight: 700;
            color: #2563eb;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-ghost {
            background: transparent;
            color: #6b7280;
            margin-left: 8px;
        }
        .btn-ghost:hover {
            color: #1a1a2e;
        }
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid #e5e7eb;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1>Session Expired</h1>
        <p>Sesi Anda telah berakhir karena tidak ada aktivitas.<br>Silakan login kembali untuk melanjutkan.</p>
        <div class="countdown">
            <span class="spinner"></span> Mengarahkan ke halaman login dalam <span id="timer">3</span> detik...
        </div>
        <div>
            <a href="/login" class="btn btn-primary">Login Sekarang</a>
            <a href="/" class="btn btn-ghost">Ke Halaman Utama</a>
        </div>
    </div>
    <script>
        var seconds = 3;
        var timerEl = document.getElementById('timer');
        var interval = setInterval(function() {
            seconds--;
            if (timerEl) timerEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '/login';
            }
        }, 1000);
    </script>
</body>
</html>
