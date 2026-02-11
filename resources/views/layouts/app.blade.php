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

    @vite(['resources/css/style.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-datetimepicker/2.7.1/css/bootstrap-material-datetimepicker.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-date-range-picker@0.21.1/dist/daterangepicker.min.css">
    @stack('styles')
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
                    <button class="st-sidebar__toggle--mobile" id="mobile-menu-toggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
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

<!-- Defer semua external scripts untuk performa lebih baik -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@vite(['resources/js/pages/main.js', 'resources/js/pages/gate-status.js'])
<div id="st-modal-container"></div>

@php
    $stReminderUrl = null;
    if (auth()->check() && auth()->user()->can('bookings.index') && \Illuminate\Support\Facades\Route::has('bookings.ajax.reminders')) {
        $stReminderUrl = route('bookings.ajax.reminders');
    }
    $stLatestUrl = \Illuminate\Support\Facades\Route::has('notifications.latest')
        ? route('notifications.latest')
        : null;
    $stMarkAllUrl = \Illuminate\Support\Facades\Route::has('notifications.markAllRead')
        ? route('notifications.markAllRead')
        : null;
    $stClearUrl = \Illuminate\Support\Facades\Route::has('notifications.clearAll')
        ? route('notifications.clearAll')
        : null;
@endphp
<script type="application/json" id="st-app-config">{!! json_encode([
    'reminderUrl' => $stReminderUrl,
    'latestUrl' => $stLatestUrl,
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
@stack('scripts')
</body>
</html>
