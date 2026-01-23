<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4285f4">
    <meta name="description" content="Sistem manajemen slot untuk warehouse gate scheduling">
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
    
    @vite(['resources/css/app.css', 'resources/css/style.css', 'resources/css/notifications.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="st-app">
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

            @can('bookings.manage')
            <a href="{{ route('bookings.index') }}" title="Booking Requests" class="st-sidebar__link{{ request()->routeIs('bookings.*') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>Bookings</span>
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

    <div class="st-main" style="display:flex;flex-direction:column;min-height:100vh;">
        <header class="st-topbar" style="flex:0 0 auto;">
            <div style="display: flex; align-items: center; gap: 12px; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <!-- Desktop sidebar toggle -->
                    <button class="st-sidebar__toggle" id="desktop-menu-toggle" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <!-- Hamburger menu for mobile -->
                    <button class="st-sidebar__toggle" id="mobile-menu-toggle" aria-label="Toggle menu" style="display: none;">
                        <span></span>
                    </button>
                    <h1 class="st-topbar__title">{{ $pageTitle ?? trim($__env->yieldContent('page_title')) ?: 'Dashboard' }}</h1>
                </div>
                <div class="st-topbar__user">
                    <div class="st-topbar__user-info">
                        <span class="st-topbar__user-name">{{ auth()->user()->full_name ?? 'User' }}</span>
                        <span class="st-topbar__user-role">{{ auth()->user()?->getRoleNames()?->first() ?? auth()->user()->role ?? 'operator' }}</span>
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
                                    <a href="{{ route('notifications.index') }}" class="st-notification-link" style="margin-left:auto;">View all</a>
                                    @if(auth()->user()->unreadNotifications->count() > 0)
                                        <a href="{{ route('notifications.markAllRead') }}" class="st-notification-link">Mark all read</a>
                                    @endif
                                </div>
                                <div class="st-notification-list">
                                    @forelse(auth()->user()->notifications()->limit(10)->get() as $notification)
                                        <a href="{{ $notification->data['action_url'] ?? '#' }}" class="st-notification-item {{ $notification->read_at ? '' : 'st-notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ $notification->data['action_url'] ?? '#' }}');">
                                            <div class="st-notification-icon" style="background: {{ $notification->data['color'] === 'red' ? '#fee2e2' : ($notification->data['color'] === 'green' ? '#dcfce7' : '#dbeafe') }}; color: {{ $notification->data['color'] === 'red' ? '#991b1b' : ($notification->data['color'] === 'green' ? '#166534' : '#1e40af') }}">
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
                                            <i class="fas fa-bell-slash" style="font-size: 1.5rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                            <p style="margin:0;font-size:0.875rem;">No notifications yet</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('profile') }}" class="st-icon-button" title="Profile">
                            <span class="st-icon-glyph">👤</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="st-icon-button" title="Logout" style="border:0;background:transparent;cursor:pointer;">
                                <span class="st-icon-glyph">⮕</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="st-content" style="flex:1;display:flex;flex-direction:column;">
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
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
                            if (confirm('Aplikasi telah diperbarui. Muat ulang untuk menggunakan versi terbaru?')) {
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

@stack('scripts')
</body>
</html>
