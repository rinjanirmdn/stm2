<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4285f4">
    <meta name="description" content="Vendor Portal - e-Docking Control System">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="e-DCS Vendor">

    <title>@yield('title', 'Vendor Portal - e-Docking Control System')</title>

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    @vite(['resources/css/style.css', 'resources/css/vendor.css', 'resources/js/app.js', 'resources/js/pages/vendor.js'])
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-datetimepicker/2.7.1/css/bootstrap-material-datetimepicker.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    @stack('styles')
</head>
<body class="vendor-app @yield('body_class')">
    <header class="vendor-header">
        <div class="vendor-header__brand">
            <img src="{{ asset('img/logo-icon2.png') }}" alt="e-DCS" class="vendor-header__logo">
            <img src="{{ asset('img/e-DCS full putih.png') }}" alt="e-Docking Control System" class="vendor-header__logo vendor-header__logo--full">

            <!-- Compact user menu for mobile (three dots) -->
            <button type="button" class="vendor-header__user-menu-btn" id="vendor-user-menu-btn" aria-label="Open user menu">
                <i class="fas fa-ellipsis-v vendor-header__user-menu-icon"></i>
            </button>
            <div class="vendor-header__user-menu" id="vendor-user-menu">
                <div class="vendor-header__user-menu-info">
                    <div class="vendor-header__user-name">{{ auth()->user()->name ?? auth()->user()->username ?? auth()->user()->email ?? '' }}</div>
                    <div class="vendor-header__user-company">{{ auth()->user()->vendor_code ?? 'Vendor' }}</div>
                </div>
                <div class="vendor-header__user-menu-actions">
                    <a href="{{ route('profile') }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm" title="Profile">
                        <i class="fas fa-user"></i>
                    </a>
                    <button type="button" class="vendor-btn vendor-btn--secondary vendor-btn--sm vendor-header__user-menu-notif" id="vendor-user-menu-notif" aria-label="Notifications" title="Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="vendor-inline-form vendor-header__user-menu-logout">
                        @csrf
                        <button type="submit" class="vendor-btn vendor-btn--secondary vendor-btn--sm" aria-label="Logout" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <nav class="vendor-header__nav">
            @can('vendor.dashboard')
                <a href="{{ route('vendor.dashboard') }}" class="vendor-nav-link{{ request()->routeIs('vendor.dashboard') ? ' active' : '' }}">
                    Dashboard
                </a>
            @endcan
            @can('vendor.bookings.index')
                <a href="{{ route('vendor.bookings.index') }}" class="vendor-nav-link{{ request()->routeIs('vendor.bookings.*') ? ' active' : '' }}">
                    My Bookings
                </a>
            @endcan
            @can('vendor.availability')
                <a href="{{ route('vendor.availability') }}" class="vendor-nav-link{{ request()->routeIs('vendor.availability') ? ' active' : '' }}">
                    Availability
                </a>
            @endcan

            <span class="vendor-nav-divider" aria-hidden="true"></span>

            @can('vendor.bookings.create')
                <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary vendor-btn--nav">
                    <i class="fas fa-plus"></i>
                    New Booking
                </a>
            @endcan
        </nav>

        <div class="vendor-header__user">
            <div class="vendor-header__user-info">
                <span class="vendor-header__user-name">{{ auth()->user()->name ?? auth()->user()->username ?? auth()->user()->email ?? '' }}</span>
                <span class="vendor-header__user-company">{{ auth()->user()->vendor_code ?? 'Vendor' }}</span>
            </div>

            <!-- Notification Component -->
            @can('notifications.index')
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
            @endcan

            <a href="{{ route('profile') }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm" title="Profile">
                <i class="fas fa-user"></i>
            </a>
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
            <div class="vendor-alert vendor-alert--success vendor-alert--autodismiss">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="vendor-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="vendor-alert vendor-alert--error vendor-alert--autodismiss">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button type="button" class="vendor-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Global Footer -->
    <footer class="vendor-footer">
        <div class="vendor-footer__content">
            <span>&copy; {{ date('Y') }} e-Docking Control System. All rights reserved.</span>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/dmuy/MDTimePicker@v2.0/dist/mdtimepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-datetimepicker/2.7.1/js/bootstrap-material-datetimepicker.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-date-range-picker@0.21.1/dist/jquery.daterangepicker.min.js"></script>
    @php
        $stVendorLatestUrl = (auth()->check() && auth()->user()->can('notifications.latest') && \Illuminate\Support\Facades\Route::has('notifications.latest'))
            ? route('notifications.latest')
            : null;
        $stVendorMarkAllUrl = (auth()->check() && auth()->user()->can('notifications.readAll') && \Illuminate\Support\Facades\Route::has('notifications.markAllRead'))
            ? route('notifications.markAllRead')
            : null;
        $stVendorClearUrl = (auth()->check() && auth()->user()->can('notifications.clearAll') && \Illuminate\Support\Facades\Route::has('notifications.clearAll'))
            ? route('notifications.clearAll')
            : null;
    @endphp
    <script type="application/json" id="st-vendor-config">{!! json_encode([
        'latestUrl' => $stVendorLatestUrl,
        'notifications' => [
            'markAllUrl' => $stVendorMarkAllUrl,
            'clearUrl' => $stVendorClearUrl,
            'readBaseUrl' => '/notifications',
        ],
    ]) !!}</script>
    <script type="application/json" id="indonesia_holidays_global">{!! json_encode($holidays ?? []) !!}</script>
    @stack('scripts')
</body>
</html>
