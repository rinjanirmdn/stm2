<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4285f4">
    <meta name="description" content="e-Docking Control System for warehouse gate scheduling">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="e-DCS">
    <meta name="application-name" content="e-DCS">
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

    <title>@yield('title', 'e-Docking Control System')</title>
    <!-- Preconnect untuk performa lebih baik -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">

    <!-- NON-BLOCKING: Fonts & icon CSS loaded async to prevent render-blocking delays -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" media="print" onload="this.media='all'">

    <!-- Fallback for no-JS -->
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    </noscript>

    @vite(['resources/css/style.css', 'resources/js/app.js'])

    <!-- NON-BLOCKING: Third-party UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-datetimepicker/2.7.1/css/bootstrap-material-datetimepicker.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-date-range-picker@0.21.1/dist/daterangepicker.min.css" media="print" onload="this.media='all'">
    @stack('styles')
</head>
@php
    $stIsDashboardRoute = request()->routeIs('dashboard');
    $stIsDisplayOnly = $stIsDashboardRoute && request()->query('display') === '1';
@endphp
<body class="st-app @yield('body_class'){{ $stIsDisplayOnly ? ' st-display-only' : '' }}">
    <!-- Mobile sidebar overlay -->
    <div class="st-sidebar__overlay" id="mobile-menu-overlay"></div>

    <aside class="st-sidebar" id="sidebar">
        <div class="st-sidebar__brand">
            <div class="st-sidebar__brand-main">
                <div class="st-sidebar__logo-stack st-sidebar__logo-stack--oneject">
                    <img src="{{ asset('img/logo-icon.png') }}" alt="Oneject" class="st-sidebar__logo st-sidebar__logo--icon st-sidebar__logo--oneject-icon">
                    <img src="{{ asset('img/logo-full.png') }}" alt="Oneject" class="st-sidebar__logo st-sidebar__logo--full st-sidebar__logo--oneject-full">
                </div>
            </div>
        </div>

        <nav class="st-sidebar__nav">
            @can('dashboard.view')
            <a href="{{ route('dashboard') }}" title="Dashboard" class="st-sidebar__link{{ request()->routeIs('dashboard') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            @endcan

            @if(auth()->user()->can('security.dashboard') && !auth()->user()->can('dashboard.view'))
            <a href="{{ route('security.dashboard') }}" title="Security Dashboard" class="st-sidebar__link{{ request()->routeIs('security.dashboard') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-shield-halved"></i>
                <span>Security</span>
            </a>
            @endif

            @can('slots.index')
            <a href="{{ route('slots.index') }}" title="Planned" class="st-sidebar__link{{ request()->routeIs('slots.index') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-calendar-days"></i>
                <span>Planned</span>
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

            @can('slots.create')
            <a href="{{ route('md_bp.index') }}" title="Business Partner" class="st-sidebar__link{{ request()->routeIs('md_bp.*') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-handshake"></i>
                <span>Business Partner</span>
            </a>
            <a href="{{ route('master.transporters.index') }}" title="Vendor Transporter" class="st-sidebar__link{{ request()->routeIs('master.transporters.*') ? ' st-sidebar__link--active' : '' }}">
                <i class="fas fa-truck"></i>
                <span>Vendor Transporter</span>
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
                    <button class="st-sidebar__toggle--mobile" id="mobile-menu-toggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="st-topbar__title">{{ $pageTitle ?? trim($__env->yieldContent('page_title')) ?: 'Dashboard' }}</h1>
                </div>

                <div class="st-topbar__center" aria-hidden="true">
                    <a href="{{ route('dashboard') }}" class="st-topbar__center-link" tabindex="-1">
                        <img src="{{ asset('img/e-Docking Control System.png') }}" alt="e-Docking Control System" class="st-topbar__center-logo">
                    </a>
                </div>

                <div class="st-topbar__user">
                    <!-- Notification Component -->
                    @if(auth()->check())
                        @can('notifications.index')
                        <div class="st-notification">
                            <button type="button" class="st-notification-btn" id="st-notification-btn">
                                <i class="fas fa-bell"></i>
                                @if(auth()->user()->unreadNotifications()->count() > 0)
                                    <span class="st-notification-badge">{{ auth()->user()->unreadNotifications()->count() }}</span>
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
                        @endcan
                    @endif

                    <details class="st-topbar__menu">
                        <summary class="st-icon-button st-topbar__menu-toggle" title="User menu" aria-label="User menu">
                            <i class="fas fa-ellipsis-v"></i>
                        </summary>
                        <div class="st-topbar__menu-dropdown" role="menu">
                            <div class="st-topbar__user-info">
                                <span class="st-topbar__user-name">{{ auth()->user()->name ?? 'User' }}</span>
                                <span class="st-topbar__user-role">{{ auth()->user()?->getRoleNames()?->first() ?? 'User' }}</span>
                            </div>
                            <div class="st-topbar__user-actions">
                                <a href="{{ route('profile') }}" class="st-icon-button" title="Profile" aria-label="Profile">
                                    <i class="fas fa-user"></i>
                                </a>
                                @can('dashboard.view')
                                    @if(!$stIsDisplayOnly)
                                        <a href="{{ $stIsDashboardRoute ? request()->fullUrlWithQuery(['display' => '1']) : route('dashboard', ['display' => '1']) }}" class="st-icon-button" title="Presentation Only" aria-label="Presentation Only" target="_blank" rel="noopener">
                                            <i class="fas fa-display"></i>
                                        </a>
                                    @endif
                                @endcan
                                <form method="POST" action="{{ route('logout') }}" class="st-inline-form">
                                    @csrf
                                    <button type="submit" class="st-icon-button st-icon-button--ghost" title="Logout" aria-label="Logout">
                                        <i class="fas fa-right-from-bracket"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main class="st-content st-content--layout">
            @php
                $stIsProfileRestricted = request()->routeIs('profile')
                    && auth()->check()
                    && !auth()->user()->can('profile.change_password');
                $stSuccessMessage = session('success') ?: (string) request()->query('_success', '');
                $stErrorMessage = (string) session('error', '');
            @endphp

            @if ($stSuccessMessage)
                @if ($stIsProfileRestricted)
                    <div class="vendor-alert vendor-alert--success vendor-alert--autodismiss">
                        <i class="fas fa-check-circle"></i>
                        {{ $stSuccessMessage }}
                        <button type="button" class="vendor-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                    </div>
                @else
                    <div class="st-alert st-alert--success st-alert--autodismiss">
                        <span class="st-alert__icon"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="st-alert__text">{{ $stSuccessMessage }}</span>
                        <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                    </div>
                @endif
            @endif

            @if ($stErrorMessage)
                @if ($stIsProfileRestricted)
                    <div class="vendor-alert vendor-alert--error vendor-alert--autodismiss">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $stErrorMessage }}
                        <button type="button" class="vendor-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                    </div>
                @else
                    <div class="st-alert st-alert--error st-alert--autodismiss">
                        <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <span class="st-alert__text">{{ $stErrorMessage }}</span>
                        <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                    </div>
                @endif
            @endif

            @can('bookings.index')
            <div id="st-reminder-banner" class="st-reminder-banner">
                <div class="st-reminder-banner__row">
                    <div class="st-reminder-banner__label">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span>Reminder: Pending bookings nearing schedule</span>
                        <span id="st-reminder-count" class="st-reminder-banner__count">0</span>
                    </div>
                    <button type="button" class="st-reminder-banner__close" onclick="this.closest('.st-reminder-banner').style.display='none'; try{var stAppConfig = JSON.parse(document.getElementById('st-app-config').textContent || '{}'); sessionStorage.setItem('st-hide-reminder-' + (stAppConfig.sessionId || 'default'), '1');}catch(e){}" aria-label="Close">&times;</button>
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
                <span>&copy; {{ date('Y') }} e-Docking Control System. All rights reserved.</span>
            </div>
        </footer>
    </div>

<!-- Defer semua external scripts untuk performa lebih baik -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js" defer></script>
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.js" defer></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js" defer></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
@vite(['resources/js/pages/main.js', 'resources/js/pages/gate-status.js'])
<div id="st-modal-container"></div>

<script>
    (function () {
        try {
            var url = new URL(window.location.href);
            if (!url.searchParams.has('_success')) return;
            url.searchParams.delete('_success');
            window.history.replaceState({}, document.title, url.toString());
        } catch (e) {
            // no-op
        }
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-dismiss internal alerts after 5 seconds
        document.querySelectorAll('.st-alert--autodismiss').forEach(function (alert) {
            setTimeout(function () {
                if (alert && alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        });

        // Auto-dismiss vendor alerts on profile page after 5 seconds
        document.querySelectorAll('.vendor-alert--autodismiss').forEach(function (alert) {
            setTimeout(function () {
                if (alert && alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        });
    });
</script>

@php
    $stReminderUrl = null;
    if (auth()->check() && auth()->user()->can('bookings.ajax.reminders') && \Illuminate\Support\Facades\Route::has('bookings.ajax.reminders')) {
        $stReminderUrl = route('bookings.ajax.reminders');
    }
    $stLatestUrl = (auth()->check() && auth()->user()->can('notifications.latest') && \Illuminate\Support\Facades\Route::has('notifications.latest'))
        ? route('notifications.latest')
        : null;
    $stMarkAllUrl = (auth()->check() && auth()->user()->can('notifications.readAll') && \Illuminate\Support\Facades\Route::has('notifications.readAll'))
        ? route('notifications.readAll')
        : null;
    $stClearUrl = (auth()->check() && auth()->user()->can('notifications.clearAll') && \Illuminate\Support\Facades\Route::has('notifications.clearAll'))
        ? route('notifications.clearAll')
        : null;
@endphp
<script type="application/json" id="st-app-config">{!! json_encode([
    'userId' => auth()->id(),
    'sessionId' => session()->getId(),
    'reminderUrl' => $stReminderUrl,
    'latestUrl' => $stLatestUrl,
    'realtime' => [
        'enabled' => true,
        // versionUrl + pollMs removed — updates arrive via WebSocket (echo.js data-updates channel)
    ],
    'notifications' => [
        'markAllUrl' => $stMarkAllUrl,
        'clearUrl' => $stClearUrl,
        'readBaseUrl' => '/notifications',
    ],
    'pwa' => [
        'enabled' => app()->environment('production'),
        'swUrl' => asset('sw.js'),
    ],
]) !!}</script>
<script type="application/json" id="indonesia_holidays_global">{!! json_encode($holidays ?? []) !!}</script>
@include('partials.input-formatters')

{{-- Global Iframe Modal for Lifecycle Actions --}}
<div id="st-global-iframe-modal" class="st-custom-modal">
    <div class="st-custom-modal-overlay" onclick="closeGlobalAjaxModal()"></div>
    <div class="st-custom-modal-container" style="max-width: 600px; width: 95%;">
        <div class="st-custom-modal-header">
            <i class="fas fa-tasks st-text-18 st-mr-8"></i>
            <span id="st-global-iframe-modal-title">Action</span>
            <button type="button" class="st-custom-modal-close" onclick="closeGlobalAjaxModal()">&times;</button>
        </div>
        <div class="st-custom-modal-body st-p-0" id="st-global-ajax-modal-body">
            {{-- AJAX content will be injected here --}}
        </div>
        <div class="st-custom-modal-footer st-justify-center">
            <div id="st-global-iframe-loader" class="st-text--muted st-text--sm" style="display: none;">
                <i class="fas fa-spinner fa-spin st-mr-6"></i> Loading form...
            </div>
        </div>
    </div>
</div>

<script>
    function openGlobalAjaxModal(title, url) {
        const modal = document.getElementById('st-global-iframe-modal');
        const modalBody = document.getElementById('st-global-ajax-modal-body');
        const titleEl = document.getElementById('st-global-iframe-modal-title');
        const loader = document.getElementById('st-global-iframe-loader');

        titleEl.textContent = title;
        modalBody.innerHTML = '';
        loader.style.display = 'block';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Add popup=1 to URL just in case backend still relies on it
        const separator = url.indexOf('?') !== -1 ? '&' : '?';
        const fetchUrl = url + separator + 'popup=1';

        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text) });
            }
            return response.text();
        })
        .then(html => {
            loader.style.display = 'none';
            // Use jQuery to inject so script tags execute natively
            if (window.$) {
                $(modalBody).html(html);
            } else {
                modalBody.innerHTML = html;
            }
            bindAjaxFormSubmit(modalBody);
            if (typeof window.initializeInputFormatters === 'function') {
                window.initializeInputFormatters(modalBody);
            }
        })
        .catch(err => {
            loader.style.display = 'none';
            modalBody.innerHTML = `<div class="st-p-16 st-text-center st-text--danger">
                <i class="fas fa-exclamation-triangle st-text-24 st-mb-8"></i>
                <div class="st-font-semibold">Failed to load content</div>
                <div class="st-text--sm">${err.message.substring(0, 100)}...</div>
            </div>`;
        });
    }

    function bindAjaxFormSubmit(container) {
        const form = container.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(form);
            const action = form.action;

            fetch(action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok && res.status !== 422) {
                    throw new Error('Server error');
                }
                return res.json().then(data => ({ status: res.status, data }));
            })
            .then(({ status, data }) => {
                // Clear existing error alerts
                const existingAlerts = form.querySelectorAll('.st-alert--error');
                existingAlerts.forEach(el => el.remove());

                if (status === 422) {
                    if (submitBtn) submitBtn.disabled = false;
                    let errorHtml = '<ul class="st-m-0 st-pl-16">';
                    let errData = data.errors || data;
                    for (const key in errData) {
                        const msg = Array.isArray(errData[key]) ? errData[key][0] : errData[key];
                        errorHtml += `<li>${msg}</li>`;
                    }
                    errorHtml += '</ul>';

                    const errorBox = document.createElement('div');
                    errorBox.className = 'st-alert st-alert--error st-mb-12';
                    errorBox.innerHTML = `
                        <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <div class="st-alert__text">
                            <div class="st-font-semibold st-mb-2">Validation Error</div>
                            <div class="st-text--sm">${errorHtml}</div>
                        </div>
                    `;
                    form.prepend(errorBox);
                    container.scrollTop = 0;
                } else if (data.success || status === 200) {
                    // Success! Reload page to show updated table/detail
                    var msg = data.message || 'Operation completed successfully';
                    var currUrl = new URL(window.location.href);
                    currUrl.searchParams.set('_success', msg);
                    window.location.href = currUrl.toString();
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            })
            .catch(err => {
                if (submitBtn) submitBtn.disabled = false;
                alert("Operation failed: " + err.message);
            });
        });
    }

    function closeGlobalAjaxModal() {
        const modal = document.getElementById('st-global-iframe-modal');
        const modalBody = document.getElementById('st-global-ajax-modal-body');

        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => { modalBody.innerHTML = ''; }, 200);
    }

    // Listen for messages from inside the iframe (success)
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'LIFECYCLE_SUCCESS') {
            closeGlobalAjaxModal();
            // Optional: show a toast here if we had a global toast, else just reload
            window.location.reload();
        }
    });
</script>

<script>
/**
 * MDTimePicker v2.0 inner-ring fix.
 * The library assigns wrong rotation classes to inner ring digits (24h mode).
 * This fix reads each inner digit's displayed number and directly sets the
 * correct CSS transform: rotate((N%12)*30  deg) on the element, and the
 * counter-rotation on the inner span so text stays upright.
 */
(function(){
    function fixInnerDigits(container) {
        var digits = container.querySelectorAll('.mdtp__digit.inner--digit');
        if (!digits.length) return;
        digits.forEach(function(d) {
            var span = d.querySelector('span');
            if (!span) return;
            var num = parseInt(span.textContent, 10);
            if (isNaN(num)) return;
            // CSS rotate(0deg) = 9 o'clock (left). Add 90° so 12 is at top.
            var deg = ((num % 12) * 30 + 90) % 360;
            // Remove any existing rotate-* class
            Array.from(d.classList).forEach(function(c) {
                if (/^rotate-\d+$/.test(c)) d.classList.remove(c);
            });
            // Apply correct rotation via inline style
            d.style.transform = 'rotate(' + deg + 'deg)';
            span.style.transform = 'rotate(-' + deg + 'deg)';
        });
    }
    var obs = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            m.addedNodes.forEach(function(n) {
                if (n.nodeType === 1 && n.classList && n.classList.contains('mdtimepicker')) {
                    setTimeout(function(){ fixInnerDigits(n); }, 80);
                }
            });
            if (m.type === 'attributes' && m.target.classList && m.target.classList.contains('mdtimepicker') && !m.target.classList.contains('hidden')) {
                setTimeout(function(){ fixInnerDigits(m.target); }, 80);
            }
        });
    });
    obs.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
})();
</script>

@stack('scripts')
</body>
</html>
