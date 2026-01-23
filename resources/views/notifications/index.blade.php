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
        <div style="display:flex; gap:0.5rem;">
            <a href="{{ route('notifications.markAllRead') }}" class="vendor-btn vendor-btn--secondary">Mark all read</a>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:0.5rem;">
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
            <a href="{{ $actionUrl }}" class="notification-item {{ $notification->read_at ? '' : 'notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ e($actionUrl) }}');" style="border:1px solid #e5e7eb; border-radius:12px;">
                <div class="notification-icon" style="background: {{ ($notification->data['color'] ?? '') === 'red' ? '#fee2e2' : ((($notification->data['color'] ?? '') === 'green') ? '#dcfce7' : '#dbeafe') }}; color: {{ ($notification->data['color'] ?? '') === 'red' ? '#991b1b' : ((($notification->data['color'] ?? '') === 'green') ? '#166534' : '#1e40af') }}">
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

    <div style="margin-top:1rem;">
        {{ $notifications->links() }}
    </div>
</div>
@else
<div class="st-card" style="padding: 1.5rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
        <h2 style="margin:0; display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-bell"></i>
            Notifications
        </h2>
        <a href="{{ route('notifications.markAllRead') }}" class="st-btn st-btn--secondary">Mark all read</a>
    </div>

    <div class="st-notification-list" style="max-height:none;">
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
            <a href="{{ $actionUrl }}" class="st-notification-item {{ $notification->read_at ? '' : 'st-notification-item--unread' }}" onclick="return markAsReadAndGo(event, '{{ $notification->id }}', '{{ e($actionUrl) }}');" style="border-radius:12px; margin-bottom:8px;">
                <div class="st-notification-icon" style="background: {{ ($notification->data['color'] ?? '') === 'red' ? '#fee2e2' : ((($notification->data['color'] ?? '') === 'green') ? '#dcfce7' : '#dbeafe') }}; color: {{ ($notification->data['color'] ?? '') === 'red' ? '#991b1b' : ((($notification->data['color'] ?? '') === 'green') ? '#166534' : '#1e40af') }}">
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

    <div style="margin-top:1rem;">
        {{ $notifications->links() }}
    </div>
</div>
@endif
@endsection
