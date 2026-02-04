@extends('vendor.layouts.vendor')

@section('title', 'Dashboard - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--dashboard')

@section('content')

<!-- Status Strip (Clickable) -->
@if(isset($stats))
<div class="vd-container">
    <div class="vd-status-strip">
        <a href="{{ route('vendor.bookings.index', ['status' => 'pending']) }}" class="vd-status-item vd-status-item--warning">
            <div class="vd-status-item__count">{{ $stats['pending_approval'] ?? 0 }}</div>
            <div class="vd-status-item__label">Pending Approval</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'approved']) }}" class="vd-status-item vd-status-item--info">
            <div class="vd-status-item__count">{{ $stats['scheduled'] ?? 0 }}</div>
            <div class="vd-status-item__label">Scheduled</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'completed']) }}" class="vd-status-item vd-status-item--success">
            <div class="vd-status-item__count">{{ $stats['completed_this_month'] ?? 0 }}</div>
            <div class="vd-status-item__label">Completed ({{ date('M') }})</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'completed']) }}" class="vd-status-item vd-status-item--warning">
            <div class="vd-status-item__count">{{ $stats['late_arrivals_today'] ?? 0 }}</div>
            <div class="vd-status-item__label">Late Arrivals Today</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'completed']) }}" class="vd-status-item vd-status-item--info">
            <div class="vd-status-item__count">{{ $stats['avg_waiting_time'] ?? 0 }}<span class="vd-status-item__unit">m</span></div>
            <div class="vd-status-item__label">Avg Waiting Time</div>
        </a>
        <a href="{{ route('vendor.bookings.index', ['status' => 'completed']) }}" class="vd-status-item vd-status-item--success">
            <div class="vd-status-item__count">{{ $stats['on_time_arrivals_today'] ?? 0 }}</div>
            <div class="vd-status-item__label">On-Time Arrivals</div>
        </a>
    </div>

    <!-- Scroll Container -->
    <div class="vd-scroll-container">
        <!-- Content Container -->
        <div class="vd-content-container">
            <!-- Recent Bookings -->
            <div class="vendor-card">
                <div class="vendor-card__header-row">
                    <h2 class="vd-section-title">
                        <i class="fas fa-clock-rotate-left"></i>
                        Recent Bookings
                    </h2>
                    <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">
                        View All
                    </a>
                </div>

    @if($recentBookings->count() > 0)
        <div>
            @foreach($recentBookings as $booking)
            <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vd-booking-row vd-booking-row--link">
                <span class="vd-booking-row__ticket vendor-text--title">{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</span>
                <span class="vd-booking-row__time">
                    <span class="vendor-text--muted">{{ $booking->planned_start->format('d M') }} · {{ $booking->planned_start->format('H:i') }}</span>
                    <div class="vendor-text--small-muted">
                        PO: {{ $booking->po_number ?? '-' }}
                        @if($booking->status === 'cancelled' && !empty($booking->approval_notes))
                            · Reason: {{ $booking->approval_notes }}
                        @endif
                    </div>
                </span>
                @php
                    $badgeColor = match($booking->status) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary',
                        default => 'secondary',
                    };
                    $badgeLabel = match($booking->status) {
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                        default => ucfirst(str_replace('_',' ', (string) $booking->status)),
                    };

                    // Arrival status logic - selalu tampilkan
                    $arrivalStatus = '-';
                    $arrivalColor = 'secondary';
                    if($booking->actual_arrival && $booking->planned_start) {
                        $arrivalDiff = $booking->actual_arrival->diffInMinutes($booking->planned_start, false);
                        if($arrivalDiff > 15) {
                            $arrivalStatus = 'Late';
                            $arrivalColor = 'danger';
                        } elseif($arrivalDiff >= -15 && $arrivalDiff <= 15) {
                            $arrivalStatus = 'On-Time';
                            $arrivalColor = 'success';
                        } else {
                            $arrivalStatus = 'Early';
                            $arrivalColor = 'info';
                        }
                    }
                @endphp
                <span class="vendor-badge vendor-badge--{{ $badgeColor }} vd-badge--sm">{{ $badgeLabel }}</span>
                <span class="vendor-badge vendor-badge--{{ $arrivalColor }} vd-badge--sm">
                    <i class="fas fa-clock vd-badge__icon"></i>{{ $arrivalStatus }}
                </span>
                <i class="fas fa-chevron-right vd-booking-row__chevron"></i>
            </a>
            @endforeach
        </div>
    @else
        <div class="vd-empty">
            <i class="fas fa-calendar-xmark vd-empty__icon"></i>
            <p class="vd-empty__text">No bookings yet</p>
            <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary vd-empty__action">
                <i class="fas fa-plus"></i> Create First Booking
            </a>
        </div>
    @endif
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="vd-footer-container">
        <div class="vd-footer-text">
            &copy; {{ date('Y') }} Slot Time Management. All rights reserved.
        </div>
    </div>
</div>
@endif
@endsection


