@extends('vendor.layouts.vendor')

@section('title', 'Dashboard - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--dashboard')

@section('content')

<!-- Status Strip (Clickable) -->
@if(isset($stats))
<div class="vd-container">
    <!-- Status Cards -->
    <div class="vd-status-strip">
        <a href="{{ route('vendor.bookings.index', ['status' => 'pending']) }}" class="vd-status-item vd-status-item--pending">
            <div class="vd-status-item__count">{{ $stats['pending'] ?? 0 }}</div>
            <div class="vd-status-item__label">Pending</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'approved']) }}" class="vd-status-item vd-status-item--scheduled">
            <div class="vd-status-item__count">{{ $stats['scheduled'] ?? 0 }}</div>
            <div class="vd-status-item__label">Scheduled</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--waiting">
            <div class="vd-status-item__count">{{ $stats['waiting'] ?? 0 }}</div>
            <div class="vd-status-item__label">Waiting</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--inprogress">
            <div class="vd-status-item__count">{{ $stats['in_progress'] ?? 0 }}</div>
            <div class="vd-status-item__label">In Progress</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--completed">
            <div class="vd-status-item__count">{{ $stats['completed'] ?? 0 }}</div>
            <div class="vd-status-item__label">Completed</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'rejected']) }}" class="vd-status-item vd-status-item--rejected">
            <div class="vd-status-item__count">{{ $stats['rejected'] ?? 0 }}</div>
            <div class="vd-status-item__label">Rejected</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--total">
            <div class="vd-status-item__count">{{ $stats['total'] ?? 0 }}</div>
            <div class="vd-status-item__label">Total</div>
        </a>
    </div>

    <!-- 3 Containers Section -->
    <div class="vd-charts-section">
        <!-- Container 1: Bar Chart -->
        <div class="vd-chart-card">
            <div class="vd-chart-header">
                <h3 class="vd-chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Status Overview
                </h3>
                <!-- Date Range Filter -->
                <div class="vd-date-filter">
                    <form method="GET" action="{{ route('vendor.dashboard') }}" class="vd-date-filter-form">
                        <input type="hidden" name="range_start" id="vd-range-start" value="{{ request('range_start', now()->startOfMonth()->format('Y-m-d')) }}">
                        <input type="hidden" name="range_end" id="vd-range-end" value="{{ request('range_end', now()->endOfMonth()->format('Y-m-d')) }}">
                        <input type="hidden" name="date_range" id="vd-date-range" value="{{ request('date_range', 'this_month') }}">

                        <button type="button" class="vd-range-picker" id="vd-range-picker">
                            <i class="fas fa-calendar"></i>
                            <span id="vd-range-picker-label"></span>
                        </button>
                        <button type="submit" class="vd-date-filter-btn">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>
                        <a href="{{ route('vendor.dashboard') }}" class="vd-date-reset-btn">
                            <i class="fas fa-times"></i>
                            Reset
                        </a>
                    </form>
                </div>
            </div>
            <div class="vd-chart-body">
                <script type="application/json" id="vendor-status-overview-data">{!! json_encode(['stats' => $stats]) !!}</script>
                <div id="vendor-status-overview-react" class="w-full"></div>
            </div>
        </div>

        <!-- Container 2: Performance -->
        <div class="vd-performance-card">
            <div class="vd-chart-header">
                <h3 class="vd-chart-title">
                    <i class="fas fa-truck"></i>
                    Truck Performance
                </h3>
            </div>
            <div class="vd-performance-body">
                <div class="vd-performance-item">
                    <div class="vd-performance-icon vd-performance-icon--success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="vd-performance-info">
                        <div class="vd-performance-label">On-Time Arrivals</div>
                        <div class="vd-performance-value">{{ $performance['on_time'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="vd-performance-item">
                    <div class="vd-performance-icon vd-performance-icon--warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="vd-performance-info">
                        <div class="vd-performance-label">Late Arrivals</div>
                        <div class="vd-performance-value">{{ $performance['late'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="vd-performance-item">
                    <div class="vd-performance-icon vd-performance-icon--info">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="vd-performance-info">
                        <div class="vd-performance-label">Avg Waiting</div>
                        <div class="vd-performance-value">{{ $performance['avg_waiting'] !== null ? $performance['avg_waiting'] . 'm' : '-' }}</div>
                    </div>
                </div>
                <div class="vd-performance-item">
                    <div class="vd-performance-icon vd-performance-icon--success">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div class="vd-performance-info">
                        <div class="vd-performance-label">Avg Process Time</div>
                        <div class="vd-performance-value">{{ $performance['avg_process'] !== null ? $performance['avg_process'] . 'm' : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container 3: Recent Bookings -->
        <div class="vd-recent-card">
            <div class="vd-chart-header">
                <h3 class="vd-chart-title">
                    <i class="fas fa-clock-rotate-left"></i>
                    Recent Bookings
                </h3>
                <a href="{{ route('vendor.bookings.index') }}" class="vd-view-all-link">View All</a>
            </div>
            <div class="vd-recent-body">
                @if($recentBookings->count() > 0)
                    @foreach($recentBookings->take(5) as $booking)
                    @php
                        // Show real slot status when booking has been converted to a slot
                        $slot = $booking->convertedSlot;
                        $displayStatus = $slot ? $slot->status : $booking->status;
                        $displayLabel = match($displayStatus) {
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'scheduled' => 'Scheduled',
                            'waiting' => 'Waiting',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'rejected' => 'Rejected',
                            'cancelled' => 'Cancelled',
                            default => ucfirst(str_replace('_', ' ', $displayStatus)),
                        };
                        $badgeColor = match($displayStatus) {
                            'pending' => 'warning',
                            'approved', 'scheduled' => 'success',
                            'waiting' => 'warning',
                            'in_progress' => 'info',
                            'completed' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'secondary',
                            default => 'secondary',
                        };
                        $ticketLabel = $slot ? ($slot->ticket_number ?? $booking->request_number ?? 'REQ-' . $booking->id) : ($booking->request_number ?? 'REQ-' . $booking->id);
                    @endphp
                    <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vd-recent-item">
                        <div class="vd-recent-main">
                            <span class="vd-recent-ticket">{{ $ticketLabel }}</span>
                            <span class="vd-recent-time">{{ $booking->planned_start->format('d M H:i') }}</span>
                        </div>
                        <div class="vd-recent-meta">
                            <span class="vendor-badge vendor-badge--{{ $badgeColor }} vd-badge--xs">{{ $displayLabel }}</span>
                        </div>
                    </a>
                    @endforeach
                @else
                    <div class="vd-recent-empty">
                        <i class="fas fa-calendar-xmark"></i>
                        <span>No recent bookings</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
    @vite(['resources/js/vendor-dashboard.js', 'resources/js/react/vendor-status-overview.jsx'])
@endpush



