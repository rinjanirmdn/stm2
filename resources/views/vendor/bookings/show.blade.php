@extends('vendor.layouts.vendor')

@section('title', 'Booking Detail - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-show')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-ticket"></i>
            Booking {{ $booking->request_number ?? ('REQ-' . $booking->id) }}
        </h1>
        <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <!-- Status Banner -->
    @if($booking->status === 'pending')
    <div class="vendor-alert vendor-alert--warning">
        <i class="fas fa-clock"></i>
        <div>
            <strong>Pending Approval</strong> - Your booking request is waiting for admin approval.
        </div>
    </div>
    @elseif($booking->status === 'cancelled')
    <div class="vendor-alert vendor-alert--error">
        <i class="fas fa-times-circle"></i>
        <div>
            <strong>Cancelled</strong> - {{ $booking->approval_notes ?? 'Your booking was cancelled.' }}
            <div class="vb-alert__action">
                <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary vendor-btn--sm">
                    <i class="fas fa-redo"></i>
                    Create New Booking
                </a>
            </div>
        </div>
    </div>
    @elseif($booking->status === 'approved')
    <div class="vendor-alert vendor-alert--success">
        <i class="fas fa-check-circle"></i>
        <strong>Approved</strong> - Your booking request has been approved.
    </div>
    @endif

    <!-- Booking Details -->
    <div class="vb-detail-grid">
        <div>
            <h3 class="vb-section-title">
                <i class="fas fa-info-circle"></i>
                Booking Information
            </h3>

            <table class="vb-table">
                <tr>
                    <td class="vb-table__label">Ticket Number</td>
                    <td class="vb-table__value--strong">{{ $booking->convertedSlot?->ticket_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Status</td>
                    <td>
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
                        @endphp
                        <span class="vendor-badge vendor-badge--{{ $badgeColor }}">{{ $badgeLabel }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="vb-table__label">COA</td>
                    <td>
                        @if(!empty($booking->coa_path))
                            <a href="{{ asset('storage/' . $booking->coa_path) }}" target="_blank" rel="noopener">View / Download</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="vb-table__label">PO Number</td>
                    <td>{{ $booking->po_number ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div>
            <h3 class="vb-section-title">
                <i class="fas fa-clock"></i>
                Schedule
            </h3>

            <table class="vb-table">
                <tr>
                    <td class="vb-table__label">Scheduled Date</td>
                    <td class="vb-table__value--strong">{{ $booking->planned_start?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Scheduled Time</td>
                    <td class="vb-table__value--strong">{{ $booking->planned_start?->format('H:i') ?? '-' }}</td>
                </tr>
                @if($booking->actual_arrival)
                <tr>
                    <td class="vb-table__label">Actual Arrival</td>
                    <td class="vb-table__value--strong">{{ $booking->actual_arrival->format('H:i') }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Arrival Status</td>
                    <td>
                        @php
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
                        @endphp
                        <span class="vendor-badge vendor-badge--{{ $arrivalColor }}">
                            <i class="fas fa-clock"></i> {{ $arrivalStatus }}
                        </span>
                        @if($arrivalDiff !== 0)
                        <span class="vb-arrival-meta">
                            ({{ abs($arrivalDiff) }} min {{ $arrivalDiff > 0 ? 'after' : 'before' }} scheduled)
                        </span>
                        @endif
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="vb-table__label">Requested At</td>
                    <td>{{ $booking->created_at?->format('d M Y H:i') ?? '-' }}</td>
                </tr>
                @if($booking->approved_at)
                <tr>
                    <td class="vb-table__label">Processed At</td>
                    <td>{{ $booking->approved_at->format('d M Y H:i') }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div>
            <h3 class="vb-section-title">
                <i class="fas fa-truck"></i>
                Vehicle Information
            </h3>

            <table class="vb-table">
                <tr>
                    <td class="vb-table__label">Truck Type</td>
                    <td>{{ $booking->truck_type ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Vehicle Number</td>
                    <td>{{ $booking->vehicle_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Driver Number</td>
                    <td>{{ $booking->driver_number ?? '-' }}</td>
                </tr>
            </table>
        </div>

        @if($booking->approval_notes || $booking->approver)
        <div>
            <h3 class="vb-section-title">
                <i class="fas fa-user-check"></i>
                Approval Info
            </h3>

            <table class="vb-table">
                @if($booking->approver)
                <tr>
                    <td class="vb-table__label">Processed By</td>
                    <td>{{ $booking->approver->full_name }}</td>
                </tr>
                @endif
                @if($booking->approval_notes)
                <tr>
                    <td class="vb-table__label">Notes</td>
                    <td>{{ $booking->approval_notes }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
    </div>

    <!-- Actions -->
    @if(in_array($booking->status, ['pending', 'approved']))
    <div class="vb-actions">
        <div class="vb-actions__row">
            @if(!empty($booking->convertedSlot?->ticket_number))
            <a href="{{ route('vendor.bookings.ticket', $booking->convertedSlot->id) }}" class="vendor-btn vendor-btn--secondary" target="_blank">
                <i class="fas fa-print"></i>
                Print Ticket
            </a>
            @endif

            @if($booking->status === 'pending')
                <form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}"
                      onsubmit="return confirm('Are you sure you want to cancel this booking?');" class="vendor-inline-form">
                    @csrf
                    <input type="hidden" name="reason" value="Cancelled by vendor">
                    <button type="submit" class="vendor-btn vendor-btn--danger">
                        <i class="fas fa-times"></i>
                        Cancel Booking
                    </button>
                </form>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
