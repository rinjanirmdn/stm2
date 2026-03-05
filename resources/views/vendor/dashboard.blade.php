@extends('vendor.layouts.vendor')

@section('title', 'Dashboard - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--dashboard')

@section('content')

<!-- Status Strip (Clickable) -->
@if(isset($stats))
<div class="vd-container">
    <div class="vd-scroll-container">
        <div class="vd-content-container">
            <!-- Status Cards -->
            <div class="vd-status-strip" title="Summary of your latest booking statuses (from planned & booking requests).">
        <a href="{{ route('vendor.bookings.index', ['status' => 'approved']) }}" class="vd-status-item vd-status-item--scheduled" title="Scheduled bookings (approved and not yet arrived).">
            <div class="vd-status-item__count">{{ $stats['scheduled'] ?? 0 }}</div>
            <div class="vd-status-item__label">Scheduled</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--waiting" title="Truck arrived, waiting in queue.">
            <div class="vd-status-item__count">{{ $stats['waiting'] ?? 0 }}</div>
            <div class="vd-status-item__label">Waiting</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--inprogress" title="Loading/Unloading in progress.">
            <div class="vd-status-item__count">{{ $stats['in_progress'] ?? 0 }}</div>
            <div class="vd-status-item__label">In Progress</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--completed" title="Process finished.">
            <div class="vd-status-item__count">{{ $stats['completed'] ?? 0 }}</div>
            <div class="vd-status-item__label">Completed</div>
        </a>
        <a href="{{ route('vendor.bookings.index') }}" class="vd-status-item vd-status-item--total" title="Total bookings.">
            <div class="vd-status-item__count">{{ $stats['total'] ?? 0 }}</div>
            <div class="vd-status-item__label">Total</div>
        </a>
    </div>

            <!-- Global Date Range Filter for all charts -->
            <div class="vd-top-filters">
                <form method="GET" action="{{ route('vendor.dashboard') }}" class="vd-date-filter-form">
                    <input type="hidden" name="range_start" id="vd-range-start" value="{{ request('range_start', now()->startOfMonth()->format('Y-m-d')) }}">
                    <input type="hidden" name="range_end" id="vd-range-end" value="{{ request('range_end', now()->endOfMonth()->format('Y-m-d')) }}">
                    <input type="hidden" name="date_range" id="vd-date-range" value="{{ request('date_range', 'this_month') }}">

                    <button type="button" class="vd-range-picker" id="vd-range-picker">
                        <i class="fas fa-calendar vd-icon"></i>
                        <span id="vd-range-picker-label"></span>
                    </button>
                    <button type="submit" class="vd-date-filter-btn">
						Filter
						</button>
						<a href="{{ route('vendor.dashboard') }}" class="vd-date-reset-btn">
							Reset
						</a>
                </form>
            </div>

            <!-- 3 Containers Section -->
            <div class="vd-charts-section">
        <!-- Container 1: Bar Chart -->
        <div class="vd-chart-card">
            <div class="vd-chart-header">
                <h3 class="vd-chart-title">
                    <i class="fas fa-chart-bar vd-icon"></i>
                    Status Overview
                    <i class="fas fa-info-circle vendor-info-pin" title="Shows counts per status (Scheduled, Waiting, In Progress, Completed) based on your planned in the selected date range."></i>
                </h3>
            </div>
            <div class="vd-chart-body">
                <script type="application/json" id="vendor-status-overview-data">{!! json_encode(['stats' => $stats]) !!}</script>
                <div id="vendor-status-overview-react" class="w-full"></div>
            </div>
        </div>

        <!-- Container 2: On Time vs Late Pie Chart -->
        <div class="vd-chart-card vd-chart-card--ontime">
            <div class="vd-chart-header">
                <h3 class="vd-chart-title">
                    <i class="fas fa-chart-pie vd-icon"></i>
                    On Time vs Late
                    <i class="fas fa-info-circle vendor-info-pin" title="Shows on-time vs late arrival ratio for your completed data this month. A truck is considered late if it arrives more than 15 minutes after the scheduled time."></i>
                </h3>
            </div>
            <div class="vd-chart-body vd-chart-body--pie">
                <script type="application/json" id="vendor-ontime-data">{!! json_encode($performance ?? []) !!}</script>
                <div id="vendor-ontime-react" class="w-full"></div>
            </div>
        </div>

        <!-- Container 3: Recent Bookings -->
        <div class="vd-recent-card">
            <div class="vd-chart-header">
                <h3 class="vd-chart-title">
                    <i class="fas fa-clock-rotate-left vd-icon"></i>
                    Recent Bookings
                    <i class="fas fa-info-circle vendor-info-pin" title="Latest booking requests and their live status (including converted tickets)."></i>
                </h3>
                <div class="vd-recent-filters">
                    @php
                        $arrivalFilter = $arrivalFilter ?? request('arrival_filter', '');
                        $baseParams = [
                            'range_start' => $rangeStart ?? request('range_start'),
                            'range_end' => $rangeEnd ?? request('range_end'),
                        ];
                    @endphp
                    <div class="vd-recent-filter-group">
                        <span class="vd-recent-filter-label">Arrival:</span>
                        <a href="{{ route('vendor.dashboard', array_filter($baseParams)) }}"
                           class="vd-recent-filter-pill {{ $arrivalFilter === '' ? 'vd-recent-filter-pill--active' : '' }}">
                            All
                        </a>
                        <a href="{{ route('vendor.dashboard', array_merge(array_filter($baseParams), ['arrival_filter' => 'ontime'])) }}"
                           class="vd-recent-filter-pill {{ $arrivalFilter === 'ontime' ? 'vd-recent-filter-pill--active' : '' }}">
                            On Time
                        </a>
                        <a href="{{ route('vendor.dashboard', array_merge(array_filter($baseParams), ['arrival_filter' => 'late'])) }}"
                           class="vd-recent-filter-pill {{ $arrivalFilter === 'late' ? 'vd-recent-filter-pill--active' : '' }}">
                            Late
                        </a>
                    </div>
                    <a href="{{ route('vendor.bookings.index') }}" class="vd-view-all-link">View All</a>
                </div>
            </div>
            <div class="vd-recent-body">
                @if($recentBookings->count() > 0)
                    @foreach($recentBookings->take(5) as $booking)
                    @php
                        // Show real booking status when booking has been converted to a slot
                        $slot = $booking->convertedSlot;
                        $displayStatus = $slot ? $slot->status : $booking->status;
                        $arrivalLabel = null;
                        $arrivalBadge = null;
                        if ($slot && $slot->planned_start && $slot->arrival_time) {
                            $diffMin = $slot->planned_start->diffInMinutes($slot->arrival_time, false);
                            if ($diffMin > 15) {
                                $arrivalLabel = 'Late';
                                $arrivalBadge = 'danger';
                            } else {
                                $arrivalLabel = 'On Time';
                                $arrivalBadge = 'success';
                            }
                        }
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
                            'approved' => 'success',
                            'scheduled' => 'secondary',
                            'waiting' => 'warning',
                            'in_progress' => 'info',
                            'completed' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'cancelled',
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
                            @if($arrivalLabel)
                                <span class="vendor-badge vendor-badge--{{ $arrivalBadge }} vd-badge--xs">{{ $arrivalLabel }}</span>
                            @endif
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
    </div>
</div>
@endif
@endsection

@push('scripts')
    {{-- vendor-dashboard.js, vendor-status-overview.jsx, vendor-ontime-chart.jsx removed per audit #10/#11 --}}
@endpush



