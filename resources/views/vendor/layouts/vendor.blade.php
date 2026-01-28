<!DOCTYPE html>
<html lang="en">
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

    @vite(['resources/css/app.css', 'resources/css/style.css', 'resources/css/vendor.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-date-range-picker@0.21.1/dist/daterangepicker.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @stack('styles')
</head>
<body class="vendor-app @yield('body_class')">
    <header class="vendor-header">
        <div class="vendor-header__brand">
            <img src="{{ asset('img/logo-full.png') }}" alt="Slot Time" class="vendor-header__logo">
            <span class="vendor-header__title">Vendor Portal</span>
        </div>

        <nav class="vendor-header__nav">
            <a href="{{ route('vendor.dashboard') }}" class="vendor-nav-link{{ request()->routeIs('vendor.dashboard') ? ' active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('vendor.bookings.index') }}" class="vendor-nav-link{{ request()->routeIs('vendor.bookings.*') ? ' active' : '' }}">
                My Bookings
            </a>
            <a href="{{ route('vendor.availability') }}" class="vendor-nav-link{{ request()->routeIs('vendor.availability') ? ' active' : '' }}">
                Availability
            </a>

            <span class="vendor-nav-divider" aria-hidden="true"></span>

            <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary vendor-btn--nav">
                <i class="fas fa-plus"></i>
                New Booking
            </a>
        </nav>

        <div class="vendor-header__user">
            <!-- Notification Component -->
            <div class="vendor-notification">
                <button type="button" class="vendor-nav-link vendor-notification__toggle" id="notification-btn">
                    <i class="fas fa-bell"></i>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="notification-badge" id="notification-count">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </button>

                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <span>Notifications</span>
                        <div class="notification-actions">
                            <button type="button" id="notification-clear" class="notification-action-btn notification-action-btn--ghost">Clear</button>
                            <button type="button" id="notification-mark-all" class="notification-action-btn">Mark all read</button>
                        </div>
                    </div>
                    <div class="notification-list">
                        @forelse(auth()->user()->notifications()->limit(10)->get() as $notification)
                            <a href="{{ $notification->data['action_url'] ?? '#' }}" class="notification-item {{ $notification->read_at ? '' : 'notification-item--unread' }}" data-notification-id="{{ $notification->id }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ $notification->data['action_url'] ?? '#' }}');">
                                <div class="notification-icon notification-icon--{{ $notification->data['color'] === 'red' ? 'red' : ($notification->data['color'] === 'green' ? 'green' : 'blue') }}">
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
                                <i class="fas fa-bell-slash notification-empty__icon"></i>
                                <p>No notifications yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="vendor-header__user-info">
                <span class="vendor-header__user-name">{{ auth()->user()->name ?? auth()->user()->username ?? auth()->user()->email ?? '' }}</span>
                <span class="vendor-header__user-company">{{ auth()->user()->vendor_code ?? 'Vendor' }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="vendor-inline-form">
                @csrf
                <button type="submit" class="vendor-btn vendor-btn--secondary vendor-btn--sm" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </header>

    <main class="vendor-main @yield('page_class')">
        <div id="vendor-notification-toast" class="vendor-toast">
            <div class="vendor-toast__inner">
                <i class="fas fa-bell vendor-toast__icon"></i>
                <div class="vendor-toast__body">
                    <div class="vendor-toast__title">New Notification</div>
                    <div id="vendor-notification-toast-text" class="vendor-toast__text"></div>
                </div>
            </div>
        </div>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-date-range-picker@0.21.1/dist/jquery.daterangepicker.min.js"></script>
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

        function updateNotificationBadge(deltaToRemove) {
            var badge = document.getElementById('notification-count');
            if (!badge) return;
            var current = parseInt(badge.textContent || '0', 10);
            if (!isFinite(current)) current = 0;
            var next = Math.max(0, current - (deltaToRemove || 0));
            if (next <= 0) {
                badge.remove();
                return;
            }
            badge.textContent = String(next);
        }

        function markAsRead(id) {
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).then(function () {
                var item = document.querySelector('[data-notification-id="' + id + '"]');
                if (item && item.classList.contains('notification-item--unread')) {
                    item.classList.remove('notification-item--unread');
                    updateNotificationBadge(1);
                }
            });
        }

        function markAllAsRead() {
            fetch('{{ route('notifications.markAllRead') }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            }).then(function () {
                document.querySelectorAll('.notification-item--unread').forEach(function (item) {
                    item.classList.remove('notification-item--unread');
                });
                var badge = document.getElementById('notification-count');
                if (badge) badge.remove();
            });
        }

        function clearAllNotifications() {
            fetch('{{ route('notifications.clearAll') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            }).then(function () {
                var list = document.querySelector('.notification-list');
                if (list) {
                    list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash notification-empty__icon"></i><p>No notifications yet</p></div>';
                }
                var badge = document.getElementById('notification-count');
                if (badge) badge.remove();
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

        @php
            $vendorLatestUrl = \Illuminate\Support\Facades\Route::has('notifications.latest')
                ? route('notifications.latest')
                : null;
        @endphp
        (function () {
            var latestUrl = {!! $vendorLatestUrl ? json_encode($vendorLatestUrl) : 'null' !!};
            var toast = document.getElementById('vendor-notification-toast');
            var toastText = document.getElementById('vendor-notification-toast-text');
            var toastTimer = null;
            var storageKey = 'vendor_last_notification_id';

            if (!latestUrl || !toast || !toastText) {
                return;
            }

            function showNotification(notification) {
                if (!notification) {
                    return;
                }
                toastText.textContent = (notification.title || 'Notification') + ' - ' + (notification.message || '');
                toast.style.display = 'block';

                if (toastTimer) {
                    clearTimeout(toastTimer);
                }
                toastTimer = setTimeout(function () {
                    toast.style.display = 'none';
                }, 3000);
            }

            function checkLatest() {
                fetch(latestUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (resp) { return resp.json(); })
                    .then(function (data) {
                        if (!data || !data.success || !data.notification) {
                            return;
                        }
                        var lastId = localStorage.getItem(storageKey);
                        if (!lastId) {
                            localStorage.setItem(storageKey, data.notification.id);
                            return;
                        }
                        if (lastId !== data.notification.id) {
                            localStorage.setItem(storageKey, data.notification.id);
                            showNotification(data.notification);
                        }
                    })
                    .catch(function () {
                        // ignore
                    });
            }

            checkLatest();
            setInterval(checkLatest, 60 * 1000);
        })();

        document.addEventListener('DOMContentLoaded', function () {
            var markAllBtn = document.getElementById('notification-mark-all');
            var clearBtn = document.getElementById('notification-clear');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    markAllAsRead();
                });
            }
            if (clearBtn) {
                clearBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    clearAllNotifications();
                });
            }
        });
    </script>
    <script type="application/json" id="indonesia_holidays_global">{!! json_encode($holidays ?? []) !!}</script>
    <script>
        window.getIndonesiaHolidays = function() {
            try {
                return JSON.parse(document.getElementById('indonesia_holidays_global').textContent || '{}');
            } catch(e) {
                return {};
            }
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.js"></script>
    @stack('scripts')
</body>
</html>
