<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4285f4">
    <meta name="description" content="Slot management system for warehouse gate scheduling">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SlotTM">
    <meta name="application-name" content="SlotTM">
    <meta name="msapplication-TileColor" content="#4285f4">
    <meta name="msapplication-config" content="/browserconfig.xml">

    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('icons/icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/icon-180x180.png') }}">

    <!-- Standard Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/icon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/icon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <title>@yield('title', 'Slot Time Management')</title>
    <!-- Preconnect untuk performa lebih baik -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">

    <!-- Fonts dengan display=swap untuk performa -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    @vite(['resources/css/app.css', 'resources/css/style.css', 'resources/css/notifications.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-datetimepicker/2.7.1/css/bootstrap-material-datetimepicker.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-date-range-picker@0.21.1/dist/daterangepicker.min.css">
</head>
<body class="st-app @yield('body_class')">
    <!-- Mobile sidebar overlay -->
    <div class="st-sidebar__overlay" id="mobile-menu-overlay"></div>

    <aside class="st-sidebar" id="sidebar">
        <div class="st-sidebar__brand">
            <div class="st-sidebar__brand-main">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Slot Time" class="st-sidebar__logo st-sidebar__logo--icon">
                <img src="{{ asset('img/logo-full.png') }}" alt="Slot Time" class="st-sidebar__logo st-sidebar__logo--full">
            </div>
        </div>

        <nav class="st-sidebar__nav">
            @can('dashboard.view')
            <a href="{{ route('dashboard') }}" title="Dashboard" class="st-sidebar__link{{ request()->routeIs('dashboard') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            @endcan

            @can('slots.index')
            <a href="{{ route('slots.index') }}" title="Slots" class="st-sidebar__link{{ request()->routeIs('slots.index') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-calendar-days"></i>
                <span>Slots</span>
            </a>
            @endcan

            @can('gates.index')
            <a href="{{ route('gates.index') }}" title="Gates" class="st-sidebar__link{{ request()->routeIs('gates.*') ? ' st-sidebar__link--active' : '' }}">
                    <i class="fas fa-door-open"></i>
                    <span>Gates</span>
                </a>
            @endcan

            @can('bookings.index')
            <a href="{{ route('bookings.index') }}" title="Booking Requests" class="st-sidebar__link{{ request()->routeIs('bookings.*') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-clipboard-list"></i>
                <span>Booking Requests</span>
            </a>
            @endcan

            @can('unplanned.index')
            <a href="{{ route('unplanned.index') }}" title="Unplanned" class="st-sidebar__link{{ request()->routeIs('unplanned.*') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-calendar-plus"></i>
                <span>Unplanned</span>
            </a>
            @endcan



            @can('reports.transactions')
            <a href="{{ route('reports.transactions') }}" title="Reports" class="st-sidebar__link{{ request()->routeIs('reports.*') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
            @endcan


            @can('trucks.index')
            <a href="{{ route('trucks.index') }}" title="Trucks" class="st-sidebar__link{{ request()->routeIs('trucks.*') ? ' st-sidebar__link--active' : '' }}">
                    <i class="fas fa-truck-field"></i>
                    <span>Trucks</span>
                </a>
            @endcan

            @can('users.index')
                <a href="{{ route('users.index') }}" title="Users" class="st-sidebar__link{{ request()->routeIs('users.*') ? ' st-sidebar__link--active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            @endcan

            @can('logs.index')
                <a href="{{ route('logs.index') }}" title="Activity Logs" class="st-sidebar__link{{ request()->routeIs('logs.*') ? ' st-sidebar__link--active' : '' }}">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>Activity Logs</span>
                </a>
            @endcan
        </nav>
    </aside>

    <div class="st-main st-main-layout">
        <header class="st-topbar st-topbar--fixed">
            <div class="st-topbar__inner">
                <div class="st-topbar__left">
                    <!-- Desktop sidebar toggle -->
                    <button class="st-sidebar__toggle" id="desktop-menu-toggle" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <!-- Hamburger menu for mobile -->
                    <button class="st-sidebar__toggle st-sidebar__toggle--mobile" id="mobile-menu-toggle" aria-label="Toggle menu">
                        <span></span>
                    </button>
                    <h1 class="st-topbar__title">{{ $pageTitle ?? trim($__env->yieldContent('page_title')) ?: 'Dashboard' }}</h1>
                </div>
                <div class="st-topbar__user">
                    <div class="st-topbar__user-info">
                        <span class="st-topbar__user-name">{{ auth()->user()->name ?? 'User' }}</span>
                        <span class="st-topbar__user-role">{{ auth()->user()?->getRoleNames()?->first() ?? 'User' }}</span>
                    </div>
                    <div class="st-topbar__user-actions">
                        <!-- Notification Component -->
                        <div class="st-notification">
                            <button type="button" class="st-notification-btn" id="st-notification-btn">
                                <i class="fas fa-bell"></i>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <span class="st-notification-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
                                @endif
                            </button>

                            <div class="st-notification-dropdown" id="st-notification-dropdown">
                                <div class="st-notification-header">
                                    <span>Notifications</span>
                                    <div class="st-notification-actions">
                                        <button type="button" class="st-notification-link" id="st-notification-clear">Clear</button>
                                        <button type="button" class="st-notification-link" id="st-notification-mark-all">Mark all read</button>
                                    </div>
                                </div>
                                <div class="st-notification-list">
                                    @forelse(auth()->user()->notifications()->limit(10)->get() as $notification)
                                        <a href="{{ $notification->data['action_url'] ?? '#' }}" class="st-notification-item {{ $notification->read_at ? '' : 'st-notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ $notification->data['action_url'] ?? '#' }}');">
                                            <div class="st-notification-icon st-notification-icon--{{ $notification->data['color'] === 'red' ? 'red' : ($notification->data['color'] === 'green' ? 'green' : 'blue') }}">
                                                <i class="{{ $notification->data['icon'] ?? 'fas fa-info' }}"></i>
                                            </div>
                                            <div class="st-notification-content">
                                                <span class="st-notification-title">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                                <span class="st-notification-message">{{ $notification->data['message'] ?? '' }}</span>
                                                <span class="st-notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="st-notification-empty">
                                            <i class="fas fa-bell-slash st-notification-empty__icon"></i>
                                            <p class="st-notification-empty__text">No notifications yet</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('profile') }}" class="st-icon-button" title="Profile">
                            <span class="st-icon-glyph">👤</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="st-inline-form">
                            @csrf
                            <button type="submit" class="st-icon-button st-icon-button--ghost" title="Logout">
                                <span class="st-icon-glyph">⮕</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="st-content st-content--layout">
            @if (session('success'))
                <div class="st-alert st-alert--success">
                    <span class="st-alert__icon"><i class="fa-solid fa-circle-check"></i></span>
                    <span class="st-alert__text">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="st-alert st-alert--error">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <span class="st-alert__text">{{ session('error') }}</span>
                </div>
            @endif

            @can('bookings.index')
            <div id="st-reminder-banner" class="st-reminder-banner">
                <div class="st-reminder-banner__row">
                    <div class="st-reminder-banner__label">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span>Reminder: Pending bookings nearing schedule</span>
                    </div>
                    <span id="st-reminder-count" class="st-reminder-banner__count">0</span>
                </div>
                <div id="st-reminder-list" class="st-reminder-banner__list"></div>
            </div>
            <div id="st-reminder-toast" class="st-reminder-toast">
                <div class="st-toast__row">
                    <i class="fas fa-bell st-toast__icon"></i>
                    <div class="st-toast__content">
                        <div class="st-toast__title">Reminder Approvals</div>
                        <div id="st-reminder-toast-text" class="st-toast__text"></div>
                    </div>
                </div>
            </div>
            <div id="st-notification-toast" class="st-notification-toast">
                <div class="st-toast__row">
                    <i class="fas fa-bell st-toast__icon"></i>
                    <div class="st-toast__content">
                        <div class="st-toast__title">New Notification</div>
                        <div id="st-notification-toast-text" class="st-toast__text"></div>
                    </div>
                </div>
            </div>
            @endcan

            @yield('content')
        </main>

        <footer class="st-footer">
            <div class="st-footer__inner">
                <span>&copy; {{ date('Y') }} Slot Time Management. All rights reserved.</span>
            </div>
        </footer>
    </div>
</div>

<!-- Defer semua external scripts untuk performa lebih baik -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@vite(['resources/js/main.js', 'resources/js/gate-status.js'])

<!-- Optimasi performa: Lazy load non-critical scripts -->
<script>
    // Optimasi: Defer non-critical initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips dan event listeners setelah DOM ready
            if (typeof window.initTooltips === 'function') {
                window.initTooltips();
            }

var stMarkAllBtn = document.getElementById('st-notification-mark-all');
var stClearBtn = document.getElementById('st-notification-clear');
if (stMarkAllBtn) {
    stMarkAllBtn.addEventListener('click', function (e) {
        e.preventDefault();
        markAllAsRead();
    });
}
if (stClearBtn) {
    stClearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        clearAllNotifications();
    });
}
        });
    } else {
        // DOM sudah ready
        if (typeof window.initTooltips === 'function') {
            window.initTooltips();
        }
    }

    // Preload critical resources untuk performa lebih baik
    if ('requestIdleCallback' in window) {
        requestIdleCallback(function() {
            // Preload fonts hanya saat idle
            var fontLink = document.createElement('link');
            fontLink.rel = 'preload';
            fontLink.as = 'font';
            fontLink.href = 'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiJ-Ek-_EeA.woff2';
            fontLink.crossOrigin = 'anonymous';
            document.head.appendChild(fontLink);
        });
    }
</script>
<script>
    window.stPrintTicket = function (url) {
        try {
            var finalUrl = url;
            if (finalUrl && finalUrl.indexOf('autoprint=1') === -1) {
                finalUrl = finalUrl + (finalUrl.indexOf('?') >= 0 ? '&' : '?') + 'autoprint=1';
            }

            var win = window.open(finalUrl, '_blank');
            if (win) {
                try { win.focus(); } catch (e) {}
                return;
            }

            var existing = document.getElementById('st-print-iframe');
            if (existing && existing.parentNode) {
                existing.parentNode.removeChild(existing);
            }

            var iframe = document.createElement('iframe');
            iframe.id = 'st-print-iframe';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.opacity = '0';
            iframe.style.pointerEvents = 'none';
            iframe.src = finalUrl;

            iframe.onload = function () {
                try {
                    setTimeout(function () {
                        if (iframe.contentWindow) {
                            try {
                                if (iframe.contentDocument) {
                                    iframe.contentDocument.title = '';
                                }
                            } catch (e) {
                                // ignore
                            }
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        }
                    }, 600);
                } catch (e) {
                    // ignore
                }
            };

            document.body.appendChild(iframe);
        } catch (e) {
            // ignore
        }
    };
</script>

@can('bookings.index')
@php
    $reminderUrl = \Illuminate\Support\Facades\Route::has('bookings.ajax.reminders')
        ? route('bookings.ajax.reminders')
        : null;
@endphp
<script>
    (function () {
        var reminderUrl = {!! $reminderUrl ? json_encode($reminderUrl) : 'null' !!};
        var banner = document.getElementById('st-reminder-banner');
        var countEl = document.getElementById('st-reminder-count');
        var listEl = document.getElementById('st-reminder-list');
        var toast = document.getElementById('st-reminder-toast');
        var toastText = document.getElementById('st-reminder-toast-text');
        var toastTimer = null;

        if (!reminderUrl || !banner || !countEl || !listEl) {
            return;
        }

        function showToast(items) {
            if (!toast || !toastText) {
                return;
            }
            if (!items || !items.length) {
                toast.style.display = 'none';
                return;
            }

            var first = items[0];
            var label = first.request_number || first.po_number || ('Request #' + first.id);
            var minutes = typeof first.minutes_to_start === 'number'
                ? Math.max(first.minutes_to_start, 0)
                : null;
            var countdown = minutes !== null ? (minutes + ' minutes to start') : 'Starts soon';
            toastText.textContent = items.length + ' pending booking(s). Nearest: ' + label + ' - ' + countdown + '.';
            toast.style.display = 'block';

            if (toastTimer) {
                clearTimeout(toastTimer);
            }
            toastTimer = setTimeout(function () {
                toast.style.display = 'none';
            }, 5000);
        }

        function renderReminder(items) {
            if (!items || !items.length) {
                banner.style.display = 'none';
                listEl.innerHTML = '';
                countEl.textContent = '0';
                showToast([]);
                return;
            }

            countEl.textContent = items.length;
            banner.style.display = 'block';

            listEl.innerHTML = items.map(function (item) {
                var label = item.request_number || item.po_number || ('Request #' + item.id);
                var supplier = item.supplier_name ? (' - ' + item.supplier_name) : '';
                var minutes = typeof item.minutes_to_start === 'number'
                    ? Math.max(item.minutes_to_start, 0)
                    : null;
                var countdown = minutes !== null ? (' (' + minutes + ' min)') : '';
                var timeText = item.planned_start ? ('Planned: ' + item.planned_start) : 'Planned: -';
                return (
                    '<a href="' + item.show_url + '" class="st-reminder-item">' +
                    '<span class="st-reminder-item__label">' + label + supplier + '</span>' +
                    '<span class="st-reminder-item__time">' + timeText + countdown + '</span>' +
                    '</a>'
                );
            }).join('');

            showToast(items);
        }

        function fetchReminders() {
            fetch(reminderUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (!data || !data.success) {
                        renderReminder([]);
                        return;
                    }
                    renderReminder(data.items || []);
                })
                .catch(function () {
                    renderReminder([]);
                });
        }

        fetchReminders();
        setInterval(fetchReminders, 30 * 60 * 1000);
    })();
</script>
@endcan

<script>
    (function () {
        @php
            $latestUrl = \Illuminate\Support\Facades\Route::has('notifications.latest')
                ? route('notifications.latest')
                : null;
        @endphp
        var latestUrl = {!! $latestUrl ? json_encode($latestUrl) : 'null' !!};
        var toast = document.getElementById('st-notification-toast');
        var toastText = document.getElementById('st-notification-toast-text');
        var toastTimer = null;
        var storageKey = 'st_last_notification_id';

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
</script>

<!-- PWA Service Worker Registration -->
<script>
@if (app()->environment('production'))
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register("{{ asset('sw.js') }}")
            .then(registration => {
                console.log('SW registered: ', registration);

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New version available
                            if (confirm('App updated. Reload to use the latest version?')) {
                                window.location.reload();
                            }
                        }
                    });
                });
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}
@endif

// PWA Install Prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    // Show install button/banner
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.style.display = 'block';
        installBtn.addEventListener('click', () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the A2HS prompt');
                } else {
                    console.log('User dismissed the A2HS prompt');
                }
                deferredPrompt = null;
            });
        });
    }
});

// Sidebar Toggle (Desktop & Mobile)
document.addEventListener('DOMContentLoaded', function() {
    const desktopToggle = document.getElementById('desktop-menu-toggle');
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-menu-overlay');
    const body = document.body; // Use body element for toggle

    console.log('Elements found:', {
        desktopToggle: !!desktopToggle,
        mobileToggle: !!mobileToggle,
        sidebar: !!sidebar,
        body: !!body
    });

    // Check screen size on load and resize
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            // Mobile view
            if (desktopToggle) desktopToggle.style.display = 'none';
            if (mobileToggle) mobileToggle.style.display = 'flex';
        } else {
            // Desktop view
            if (desktopToggle) desktopToggle.style.display = 'flex';
            if (mobileToggle) mobileToggle.style.display = 'none';
        }
    }

    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);

    // Desktop toggle functionality
    if (desktopToggle && body) {
        console.log('Setting up desktop toggle listener');
        desktopToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Desktop toggle clicked');

            // Toggle sidebar collapsed state on body
            body.classList.toggle('st-app--sidebar-collapsed');

            console.log('Sidebar toggled');
        });
    } else {
        console.log('Desktop toggle or body not found');
    }

    // Mobile toggle functionality (existing)
    if (mobileToggle && sidebar && overlay) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('st-sidebar--mobile-open');
            overlay.classList.toggle('st-sidebar__overlay--active');
            document.body.style.overflow = sidebar.classList.contains('st-sidebar--mobile-open') ? 'hidden' : '';
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('st-sidebar--mobile-open');
            overlay.classList.remove('st-sidebar__overlay--active');
            document.body.style.overflow = '';
        });

        // Close menu when clicking links
        const sidebarLinks = sidebar.querySelectorAll('.st-sidebar__link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.remove('st-sidebar--mobile-open');
                overlay.classList.remove('st-sidebar__overlay--active');
                document.body.style.overflow = '';
            });
        });
    }
});

// Notification Toggle
const stNotifBtn = document.getElementById('st-notification-btn');
const stNotifDropdown = document.getElementById('st-notification-dropdown');

if (stNotifBtn && stNotifDropdown) {
    stNotifBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        stNotifDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
        if (!stNotifDropdown.contains(e.target) && !stNotifBtn.contains(e.target)) {
            stNotifDropdown.classList.remove('show');
        }
    });
}

function markAsRead(id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
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
        document.querySelectorAll('.st-notification-item--unread').forEach(function (item) {
            item.classList.remove('st-notification-item--unread');
        });
        var badge = document.querySelector('.st-notification-badge');
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
        var list = document.querySelector('.st-notification-list');
        if (list) {
            list.innerHTML = '<div class="st-notification-empty"><i class="fas fa-bell-slash st-notification-empty__icon"></i><p class="st-notification-empty__text">No notifications yet</p></div>';
        }
        var badge = document.querySelector('.st-notification-badge');
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
                // If absolute URL, navigate using current origin + its path (avoid wrong APP_URL host)
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

// Handle app installed event
window.addEventListener('appinstalled', (evt) => {
    console.log('PWA was installed');
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.style.display = 'none';
    }
});
</script>

<div id="st-modal-container"></div>

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
@stack('scripts')
</body>
</html>
