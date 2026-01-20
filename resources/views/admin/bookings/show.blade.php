@extends('layouts.app')

@section('title', 'Booking Detail')
@section('page_title', 'Booking Detail')

@section('content')
<div class="st-card">
    <div class="st-card__header">
        <h2 class="st-card__title">
            <i class="fas fa-ticket"></i>
            {{ $booking->ticket_number }}
        </h2>
        <div class="st-card__actions">
            <a href="{{ route('bookings.index') }}" class="st-button st-button--secondary">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    @if($booking->status === 'pending_approval')
    <div class="st-alert st-alert--warning">
        <i class="fas fa-clock"></i>
        <div>
            <strong>Pending Approval</strong> - This booking request is waiting for your action.
        </div>
    </div>
    @elseif($booking->status === 'pending_vendor_confirmation')
    <div class="st-alert st-alert--info">
        <i class="fas fa-hourglass-half"></i>
        <div>
            <strong>Awaiting Vendor</strong> - Waiting for vendor to confirm the rescheduled time.
        </div>
    </div>
    @elseif($booking->status === 'scheduled')
    <div class="st-alert st-alert--success">
        <i class="fas fa-check-circle"></i>
        <strong>Confirmed</strong> - This booking is approved and scheduled.
    </div>
    @elseif($booking->status === 'rejected')
    <div class="st-alert st-alert--danger">
        <i class="fas fa-times-circle"></i>
        <strong>Rejected</strong> - {{ $booking->approval_notes }}
    </div>
    @endif

    <!-- Booking Details -->
    <div class="st-grid st-grid--3">
        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-info-circle"></i>
                Request Information
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>Ticket</td>
                    <td><strong>{{ $booking->ticket_number }}</strong></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>
                        <span class="st-badge st-badge--{{ $booking->status_badge_color }}">
                            {{ $booking->status_label }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Vendor</td>
                    <td>{{ $booking->vendor?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Requested By</td>
                    <td>{{ $booking->requester?->full_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Direction</td>
                    <td>
                        <span class="st-badge st-badge--{{ $booking->direction === 'inbound' ? 'info' : 'warning' }}">
                            {{ ucfirst($booking->direction) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-file-invoice"></i>
                PO Items
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>PO Number</td>
                    <td><strong>{{ $poNumber ?? ($booking->po?->po_number ?? '-') }}</strong></td>
                </tr>
            </table>

            @if(!empty($poItems))
                <div class="st-table-wrapper" style="margin-top: 10px;">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th style="width:80px;">Item</th>
                                <th>Material</th>
                                <th style="width:140px; text-align:right;">Qty PO</th>
                                <th style="width:140px; text-align:right;">Qty Booked</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($poItems as $it)
                                @php
                                    $itemNo = (string) ($it['item_no'] ?? '');
                                    $mat = trim((string) ($it['material'] ?? ''));
                                    $desc = trim((string) ($it['description'] ?? ''));
                                    $uom = trim((string) ($it['uom'] ?? ''));
                                    $qtyPo = $it['qty'] ?? null;
                                    $qtyBooked = $it['qty_booked_slot'] ?? 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $itemNo }}</strong></td>
                                    <td>{{ $mat }}{{ $desc !== '' ? (' - ' . $desc) : '' }}</td>
                                    <td style="text-align:right;">{{ $qtyPo !== null ? $qtyPo : '-' }} {{ $uom }}</td>
                                    <td style="text-align:right;"><strong>{{ $qtyBooked }}</strong> {{ $uom }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="margin-top:10px; color: var(--st-text-muted); font-size: 13px;">No PO item details available for this booking.</div>
            @endif
        </div>

        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-calendar-alt"></i>
                Schedule
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>Warehouse</td>
                    <td>{{ $booking->warehouse?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Gate</td>
                    <td>{{ $booking->plannedGate?->name ?? 'To be assigned' }}</td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td><strong>{{ $booking->planned_start?->format('d M Y') ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>Time</td>
                    <td><strong>{{ $booking->planned_start?->format('H:i') ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>Duration</td>
                    <td>{{ $booking->planned_duration }} min</td>
                </tr>
                @if($booking->original_planned_start && $booking->original_planned_start->ne($booking->planned_start))
                <tr>
                    <td>Original Request</td>
                    <td style="text-decoration: line-through; color: var(--st-text-muted);">
                        {{ $booking->original_planned_start->format('d M Y H:i') }}
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <div class="st-detail-section">
            <h3 class="st-detail-title">
                <i class="fas fa-truck"></i>
                Vehicle Info
            </h3>
            <table class="st-detail-table">
                <tr>
                    <td>Truck Type</td>
                    <td>{{ $booking->truck_type ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Vehicle Number</td>
                    <td>{{ $booking->vehicle_number_snap ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Requested At</td>
                    <td>{{ $booking->requested_at?->format('d M Y H:i') ?? '-' }}</td>
                </tr>
                @if($booking->approved_at)
                <tr>
                    <td>Processed At</td>
                    <td>{{ $booking->approved_at->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Processed By</td>
                    <td>{{ $booking->approver?->full_name ?? '-' }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    @if($booking->status === 'pending_approval')
    <div class="st-action-section">
        <h3 class="st-detail-title">
            <i class="fas fa-gavel"></i>
            Actions
        </h3>
        
        <div class="st-action-buttons-group">
            @can('bookings.approve')
            <form method="POST" action="{{ route('bookings.approve', $booking->id) }}" style="display: inline;">
                @csrf
                <button type="submit" class="st-button st-button--success st-button--lg" onclick="return confirm('Approve this booking with the current schedule?')">
                    <i class="fas fa-check"></i>
                    Approve Booking
                </button>
            </form>
            @endcan

            @can('bookings.reschedule')
            <a href="{{ route('bookings.reschedule', $booking->id) }}" class="st-button st-button--warning st-button--lg">
                <i class="fas fa-calendar-alt"></i>
                Reschedule
            </a>
            @endcan

            @can('bookings.reject')
            <button type="button" class="st-button st-button--danger st-button--lg" onclick="document.getElementById('reject-section').style.display='block'; this.style.display='none';">
                <i class="fas fa-times"></i>
                Reject Booking
            </button>
            @endcan
        </div>

        <!-- Inline Reject Form -->
        <div id="reject-section" style="display: none; margin-top: 1.5rem; padding: 1.5rem; background: var(--st-danger-bg, #fee2e2); border-radius: 12px;">
            <form method="POST" action="{{ route('bookings.reject', $booking->id) }}">
                @csrf
                <h4 style="margin: 0 0 1rem; color: var(--st-danger, #dc2626);">
                    <i class="fas fa-times-circle"></i>
                    Reject Booking
                </h4>
                <div class="st-form-group">
                    <label class="st-label">Reason for Rejection <span class="st-required">*</span></label>
                    <textarea name="reason" class="st-textarea" rows="3" required 
                              placeholder="Please provide a clear reason for rejection..."></textarea>
                </div>
                <div class="st-action-buttons">
                    <button type="submit" class="st-button st-button--danger">
                        <i class="fas fa-times"></i>
                        Confirm Rejection
                    </button>
                    <button type="button" class="st-button st-button--secondary" onclick="document.getElementById('reject-section').style.display='none';">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<!-- Booking History -->
@if($booking->bookingHistories && $booking->bookingHistories->count() > 0)
<div class="st-card">
    <div class="st-card__header">
        <h2 class="st-card__title">
            <i class="fas fa-history"></i>
            Activity History
        </h2>
    </div>
    
    <div class="st-timeline">
        @foreach($booking->bookingHistories as $history)
        <div class="st-timeline__item">
            <div class="st-timeline__marker"></div>
            <div class="st-timeline__content">
                <div class="st-timeline__header">
                    <span class="st-badge st-badge--{{ $history->action_badge_color }}">
                        {{ $history->action_label }}
                    </span>
                    <span class="st-timeline__user">by {{ $history->performer?->full_name ?? 'System' }}</span>
                    <span class="st-timeline__time">{{ $history->created_at->format('d M Y H:i') }}</span>
                </div>
                
                @if($history->notes)
                <p class="st-timeline__notes">{{ $history->notes }}</p>
                @endif
                
                @if($history->old_planned_start || $history->new_planned_start)
                <div class="st-timeline__changes">
                    @if($history->old_planned_start)
                    <span class="st-text-strikethrough">{{ \Carbon\Carbon::parse($history->old_planned_start)->format('d M Y H:i') }}</span>
                    @endif
                    @if($history->new_planned_start)
                    <span> → {{ \Carbon\Carbon::parse($history->new_planned_start)->format('d M Y H:i') }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<style>
.st-grid--3 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 1.5rem 0;
}

.st-detail-section {
    background: var(--st-surface-alt, #f8fafc);
    padding: 1.25rem;
    border-radius: 12px;
}

.st-detail-title {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--st-text-secondary, #64748b);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.st-detail-table {
    width: 100%;
}

.st-detail-table td {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--st-border, #e5e7eb);
}

.st-detail-table td:first-child {
    color: var(--st-text-muted, #64748b);
    width: 40%;
}

.st-action-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--st-border, #e5e7eb);
}

.st-action-buttons-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.st-button--lg {
    padding: 1rem 2rem;
    font-size: 1rem;
}

.st-timeline {
    padding-left: 2rem;
    position: relative;
}

.st-timeline__item {
    position: relative;
    padding: 0 0 1.5rem 1.5rem;
    border-left: 2px solid var(--st-border, #e5e7eb);
}

.st-timeline__item:last-child {
    padding-bottom: 0;
    border-left-color: transparent;
}

.st-timeline__marker {
    position: absolute;
    left: -0.5rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    background: var(--st-surface, #fff);
    border: 2px solid var(--st-primary, #3b82f6);
    border-radius: 50%;
}

.st-timeline__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.st-timeline__user {
    color: var(--st-text-muted, #64748b);
    font-size: 0.875rem;
}

.st-timeline__time {
    color: var(--st-text-muted, #94a3b8);
    font-size: 0.75rem;
    margin-left: auto;
}

.st-timeline__notes {
    margin: 0.5rem 0;
    color: var(--st-text-secondary, #475569);
    font-size: 0.875rem;
}

.st-timeline__changes {
    font-size: 0.875rem;
    color: var(--st-text-muted, #64748b);
}

.st-text-strikethrough {
    text-decoration: line-through;
}
</style>
@endsection
