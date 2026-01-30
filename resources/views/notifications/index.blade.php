@php
    $isVendor = auth()->user()
        && method_exists(auth()->user(), 'hasRole')
        && auth()->user()->hasRole('vendor');
@endphp
@extends($isVendor ? 'vendor.layouts.vendor' : 'layouts.app')

@section('title', 'Notifications')

@section('content')
@if($isVendor)
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-bell"></i>
            Notifications
        </h1>
        <div class="vendor-flex vendor-gap-8">
            <a href="{{ route('notifications.markAllRead') }}" class="vendor-btn vendor-btn--secondary">Mark all read</a>
        </div>
    </div>

    <div class="vendor-flex vendor-flex-col vendor-gap-8">
        @forelse($notifications as $notification)
            @php
                $actionUrl = (string) ($notification->data['action_url'] ?? '#');
                if (preg_match('#^https?://#i', $actionUrl)) {
                    $parts = parse_url($actionUrl) ?: [];
                    $actionUrl = ($parts['path'] ?? '')
                        . (isset($parts['query']) ? ('?' . $parts['query']) : '')
                        . (isset($parts['fragment']) ? ('#' . $parts['fragment']) : '');
                    $actionUrl = $actionUrl !== '' ? $actionUrl : '/';
                }
            @endphp
            <a href="{{ $actionUrl }}" class="notification-item vendor-border vendor-rounded-12 {{ $notification->read_at ? '' : 'notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ e($actionUrl) }}');">
                <div class="notification-icon {{ ($notification->data['color'] ?? '') === 'red' ? 'notification-icon--red' : ((($notification->data['color'] ?? '') === 'green') ? 'notification-icon--green' : 'notification-icon--blue') }}">
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

    <div class="vendor-mt-16">
        {{ $notifications->links() }}
    </div>
</div>
@else
<div class="st-card st-p-24">
    <div class="st-flex st-align-center st-justify-between st-gap-16 st-mb-16">
        <h2 class="st-flex st-align-center st-gap-8 st-m-0">
            <i class="fas fa-bell"></i>
            Notifications
        </h2>
        <a href="{{ route('notifications.markAllRead') }}" class="st-btn st-btn--outline-primary">Mark all read</a>
    </div>

    <div class="st-notification-list st-maxh-none">
        @forelse($notifications as $notification)
            @php
                $actionUrl = (string) ($notification->data['action_url'] ?? '#');
                if (preg_match('#^https?://#i', $actionUrl)) {
                    $parts = parse_url($actionUrl) ?: [];
                    $actionUrl = ($parts['path'] ?? '')
                        . (isset($parts['query']) ? ('?' . $parts['query']) : '')
                        . (isset($parts['fragment']) ? ('#' . $parts['fragment']) : '');
                    $actionUrl = $actionUrl !== '' ? $actionUrl : '/';
                }
            @endphp
            <a href="{{ $actionUrl }}" class="st-notification-item st-rounded-12 st-mb-8 {{ $notification->read_at ? '' : 'st-notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ e($actionUrl) }}');">
                <div class="st-notification-icon {{ ($notification->data['color'] ?? '') === 'red' ? 'st-notification-icon--red' : ((($notification->data['color'] ?? '') === 'green') ? 'st-notification-icon--green' : 'st-notification-icon--blue') }}">
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

    <div class="st-mt-16">
        {{ $notifications->links() }}
    </div>
</div>
@endif
@endsection
