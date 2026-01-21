<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4285f4">
    <meta name="description" content="Vendor Portal - Slot Time Management">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SlotTM Vendor">

    <title>@yield('title', 'Vendor Portal - Slot Time Management')</title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    @vite(['resources/css/app.css', 'resources/css/style.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        .vendor-app {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .vendor-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
        }
        
        .vendor-header__brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .vendor-header__logo {
            height: 40px;
        }
        
        .vendor-header__title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .vendor-header__nav {
            display: flex;
            gap: 0.5rem;
        }
        
        .vendor-nav-link {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .vendor-nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .vendor-nav-link.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .vendor-header__user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .vendor-header__user-info {
            text-align: right;
        }
        
        .vendor-header__user-name {
            font-weight: 600;
            display: block;
        }
        
        .vendor-header__user-company {
            font-size: 0.875rem;
            opacity: 0.85;
        }
        
        .vendor-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .vendor-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .vendor-card__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .vendor-card__title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .vendor-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .vendor-stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease;
        }
        
        .vendor-stat-card:hover {
            transform: translateY(-2px);
        }
        
        .vendor-stat-card--warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        
        .vendor-stat-card--info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }
        
        .vendor-stat-card--success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        }
        
        .vendor-stat-card--primary {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        }
        
        .vendor-stat-card__icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .vendor-stat-card--warning .vendor-stat-card__icon {
            background: #f59e0b;
            color: white;
        }
        
        .vendor-stat-card--info .vendor-stat-card__icon {
            background: #3b82f6;
            color: white;
        }
        
        .vendor-stat-card--success .vendor-stat-card__icon {
            background: #10b981;
            color: white;
        }
        
        .vendor-stat-card--primary .vendor-stat-card__icon {
            background: #8b5cf6;
            color: white;
        }
        
        .vendor-stat-card__content {
            flex: 1;
        }
        
        .vendor-stat-card__value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .vendor-stat-card__label {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .vendor-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9375rem;
        }
        
        .vendor-btn--primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .vendor-btn--primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .vendor-btn--success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .vendor-btn--danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .vendor-btn--secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .vendor-btn--sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .vendor-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .vendor-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .vendor-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .vendor-table tr:hover td {
            background: #f8fafc;
        }
        
        .vendor-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .vendor-badge--warning {
            background: #fef3c7;
            color: #92400e;
        }

        .vendor-badge--pending_approval {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .vendor-badge--success {
            background: #dcfce7;
            color: #166534;
        }
        
        .vendor-badge--info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .vendor-badge--danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .vendor-badge--secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .vendor-form-group {
            margin-bottom: 1.25rem;
        }
        
        .vendor-form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .vendor-form-input,
        .vendor-form-select,
        .vendor-form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }
        
        .vendor-form-input:focus,
        .vendor-form-select:focus,
        .vendor-form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .vendor-alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .vendor-alert--success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .vendor-alert--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .vendor-alert--warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .vendor-alert--info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .vendor-footer {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .vendor-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .vendor-header__nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .vendor-main {
                padding: 1rem;
            }
            
            .vendor-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Notification Styles */
        .vendor-notification {
            position: relative;
            margin-right: 0.5rem;
        }
        
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 99px;
            font-weight: bold;
            border: 2px solid #1e40af;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: -80px;
            width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            overflow: hidden;
            margin-top: 1rem;
            display: none;
            color: #1e293b;
        }

        .notification-dropdown.show {
            display: block;
        }

        .notification-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            gap: 0.75rem;
            text-decoration: none;
            color: #334155;
            transition: background 0.2s;
            align-items: start;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item--unread {
            background: #eff6ff;
        }
        
        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.875rem;
        }

        .notification-content p {
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.25rem;
            display: block;
        }

        .notification-empty {
            padding: 2rem;
            text-align: center;
            color: #94a3b8;
        }
    </style>
</head>
<body class="vendor-app">
    <header class="vendor-header">
        <div class="vendor-header__brand">
            <img src="{{ asset('img/logo-full.png') }}" alt="Slot Time" class="vendor-header__logo">
            <span class="vendor-header__title">Vendor Portal</span>
        </div>
        
        <nav class="vendor-header__nav">
            <!-- Single Page App Feel - No Navigation Links -->
            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Vendor Self-Service Portal</span>
        </nav>
        
        <div class="vendor-header__user">
            <!-- Notification Component -->
            <div class="vendor-notification">
                <button type="button" class="vendor-nav-link" id="notification-btn" style="background: transparent; border: none; cursor: pointer; position: relative;">
                    <i class="fas fa-bell"></i>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="notification-badge" id="notification-count">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </button>
                
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <span>Notifications</span>
                        <a href="{{ route('notifications.index') }}" class="vendor-btn--secondary" style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; text-decoration: none;">View all</a>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <a href="{{ route('notifications.markAllRead') }}" class="vendor-btn--secondary" style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; text-decoration: none;">Mark all read</a>
                        @endif
                    </div>
                    <div class="notification-list">
                        @forelse(auth()->user()->notifications()->limit(10)->get() as $notification)
                            <a href="{{ $notification->data['action_url'] ?? '#' }}" class="notification-item {{ $notification->read_at ? '' : 'notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ $notification->data['action_url'] ?? '#' }}');">
                                <div class="notification-icon" style="background: {{ $notification->data['color'] === 'red' ? '#fee2e2' : ($notification->data['color'] === 'green' ? '#dcfce7' : '#dbeafe') }}; color: {{ $notification->data['color'] === 'red' ? '#991b1b' : ($notification->data['color'] === 'green' ? '#166534' : '#1e40af') }}">
                                    <i class="{{ $notification->data['icon'] ?? 'fas fa-info' }}"></i>
                                </div>
                                <div class="notification-content">
                                    <p><strong>{{ $notification->data['title'] ?? 'Notification' }}</strong></p>
                                    <p>{{ $notification->data['message'] ?? '' }}</p>
                                    <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="notification-empty">
                                <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                <p>No notifications yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="vendor-header__user-info">
                <span class="vendor-header__user-name">{{ auth()->user()->full_name }}</span>
                <span class="vendor-header__user-company">{{ auth()->user()->vendor?->name ?? 'Vendor' }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="vendor-btn vendor-btn--secondary vendor-btn--sm" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </header>

    <main class="vendor-main">
        @if (session('success'))
            <div class="vendor-alert vendor-alert--success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="vendor-alert vendor-alert--error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="vendor-footer">
        &copy; {{ date('Y') }} Slot Time Management. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Notification Toggle
            const notifBtn = document.getElementById('notification-btn');
            const notifDropdown = document.getElementById('notification-dropdown');
            
            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('show');
                });
                
                document.addEventListener('click', function(e) {
                    if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                        notifDropdown.classList.remove('show');
                    }
                });
            }
        });

        function markAsRead(id) {
            // Optional: Call AJAX to mark specific notification as read without reload
            // For now, let the backend link handle it or just proceed to link
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            });
        }

        function markAsReadAndGo(e, id, url) {
            try {
                if (e && typeof e.preventDefault === 'function') {
                    e.preventDefault();
                }
                function normalizeUrl(u) {
                    try {
                        var s = String(u || '');
                        if (!s) return '#';
                        if (s.indexOf('://') !== -1) {
                            var parsed = new URL(s);
                            return parsed.pathname + parsed.search + parsed.hash;
                        }
                        return s;
                    } catch (ex) {
                        return '#';
                    }
                }

                var target = normalizeUrl(url);
                markAsRead(id);
                setTimeout(function () {
                    window.location.href = target;
                }, 80);
                return false;
            } catch (err) {
                return true;
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
