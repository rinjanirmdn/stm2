@extends('vendor.layouts.vendor')

@section('title', 'Dashboard - Vendor Portal')

@section('content')
<style>
    /* Vendor Dashboard Layout Specific */
    .vendor-app .vendor-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: none;
        padding: 24px;
        margin: 0;
        box-sizing: border-box;
        overflow: hidden;
    }

    .vd-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 72px - 48px);
        width: 100%;
        margin: 0;
        background: #f1f5f9;
        border-radius: 12px;
        overflow: hidden;
    }

    .vd-scroll-container {
        flex: 1;
        overflow-y: auto;
        background: #f1f5f9;
    }

    .vd-content-container {
        background: #ffffff;
        margin: 0;
        min-height: 100%;
    }

    .vd-footer-container {
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
        padding: 16px 20px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 0 0 12px 12px;
    }

    .vd-status-strip {
        display: flex;
        gap: 0;
        background: #ffffff;
        border-radius: 12px 12px 0 0;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        flex-shrink: 0;
    }
    .vd-status-item {
        flex: 1;
        padding: 16px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        border-right: 1px solid #e5e7eb;
        text-decoration: none;
        color: inherit;
    }
    .vd-status-item:last-child { border-right: none; }
    .vd-status-item:hover { background: #f8fafc; }
    .vd-status-item__count {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    .vd-status-item__label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .vd-status-item--warning .vd-status-item__count {
        background: #fbbf24 !important;
        color: #ffffff !important;
        border: 1px solid #f59e0b;
    }
    .vd-status-item--danger .vd-status-item__count { color: #ef4444; }
    .vd-status-item--info .vd-status-item__count { color: #3b82f6; }
    .vd-status-item--success .vd-status-item__count { color: #10b981; }

    .vd-action-box {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px solid #f59e0b;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .vd-action-box__title {
        font-size: 14px;
        font-weight: 700;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .vd-action-box__list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .vd-action-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border-radius: 8px;
        padding: 12px 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .vd-action-item__info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .vd-action-item__ticket {
        font-weight: 600;
        color: #1e293b;
    }
    .vd-action-item__desc {
        font-size: 12px;
        color: #64748b;
    }

    .vd-section-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .vd-booking-row {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
        gap: 12px;
    }
    .vd-booking-row:last-child { border-bottom: none; }
    .vd-booking-row__ticket { min-width: 100px; }
    .vd-booking-row__time { flex: 1; }

    @media (max-width: 768px) {
        .vd-status-strip { flex-wrap: wrap; }
        .vd-status-item { flex: 1 1 50%; border-bottom: 1px solid #e5e7eb; }
    }
</style>

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
            <div class="vd-status-item__count">{{ $stats['avg_waiting_time'] ?? 0 }}<span style="font-size: 14px;">m</span></div>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 class="vd-section-title" style="margin: 0;">
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
            <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vd-booking-row" style="text-decoration: none;">
                <span class="vd-booking-row__ticket st-card__title">{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</span>
                <span class="vd-booking-row__time">
                    <span class="st-text--muted">{{ $booking->planned_start->format('d M') }} · {{ $booking->planned_start->format('H:i') }}
                    @if($booking->convertedSlot?->plannedGate)
                        · Gate {{ $booking->convertedSlot->plannedGate->gate_number }}
                    @endif
                    </span>
                    <div class="st-text--small-muted">
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
                <span class="st-badge st-badge--{{ $badgeColor }}">{{ $badgeLabel }}</span>
                <span class="st-badge st-badge--{{ $arrivalColor }}" style="font-size: 11px;">
                    <i class="fas fa-clock" style="font-size: 10px; margin-right: 2px;"></i>{{ $arrivalStatus }}
                </span>
                <i class="fas fa-chevron-right" style="color: #94a3b8;"></i>
            </a>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
            <i class="fas fa-calendar-xmark" style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
            <p style="margin: 0;">No bookings yet</p>
            <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary" style="margin-top: 12px;">
                <i class="fas fa-plus"></i> Create First Booking
            </a>
        </div>
    @endif
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="vd-footer-container">
        <div style="text-align: center; color: #64748b; font-size: 14px;">
            &copy; {{ date('Y') }} Slot Time Management. All rights reserved.
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
});
</script>
@endpush
