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
    </div>

    <!-- Status Banner -->
    @php
        $isRescheduled = ($booking->convertedSlot?->approval_action ?? null) === \App\Models\Slot::APPROVAL_RESCHEDULED;
    @endphp

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
        @if($isRescheduled)
            <strong>Rescheduled & Approved</strong> - Your booking has been rescheduled. Please follow the new schedule below.
        @else
            <strong>Approved</strong> - Your booking request has been approved.
        @endif
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
                    <td class="vb-table__value--strong">
                        @if($booking->status === 'approved' && $booking->convertedSlot)
                            {{ $booking->convertedSlot->ticket_number ?? 'Generating...' }}
                        @elseif($booking->status === 'approved' && $booking->converted_slot_id)
                            <span class="vendor-muted-text">Slot linked, processing ticket...</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="vb-table__label">Gate</td>
                    <td class="vb-table__value--strong">
                        @php
                            $gateDisplay = '-';
                            if ($booking->status === 'approved') {
                                if ($booking->convertedSlot?->plannedGate) {
                                    $whCode = (string) ($booking->convertedSlot?->warehouse?->wh_code ?? '');
                                    $gateNo = (string) ($booking->convertedSlot?->plannedGate?->gate_number ?? '');
                                    $gateDisplay = app(\App\Services\SlotService::class)->getGateDisplayName($whCode, $gateNo);
                                } elseif ($booking->planned_gate_id) {
                                    // Fallback to request data if slot relation is missing gate info
                                    $whCode = (string) ($booking->warehouse?->wh_code ?? '');
                                    $gateNo = (string) ($booking->plannedGate?->gate_number ?? '');
                                    $gateDisplay = app(\App\Services\SlotService::class)->getGateDisplayName($whCode, $gateNo);
                                }
                            }
                        @endphp
                        {{ (string)$gateDisplay !== '' ? $gateDisplay : '-' }}
                    </td>
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
                    <td class="vb-table__label">PO Number</td>
                    <td>{{ $booking->po_number ?? '-' }}</td>
                </tr>
                @if(($isInternalVendor ?? false) && $booking->supplier_name)
                <tr>
                    <td class="vb-table__label">Vendor Name</td>
                    <td class="vb-table__value--strong">{{ $booking->supplier_name }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div>
            <h3 class="vb-section-title">
                <i class="fas fa-clock"></i>
                Schedule
            </h3>

            <table class="vb-table">
                @if($isRescheduled)
                <tr>
                    <td class="vb-table__label">Schedule Type</td>
                    <td>
                        <span class="vendor-badge vendor-badge--info">Rescheduled</span>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="vb-table__label">Scheduled Date</td>
                    <td class="vb-table__value--strong">{{ $booking->planned_start?->format('d-m-Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Scheduled Time</td>
                    <td class="vb-table__value--strong">{{ $booking->planned_start?->format('H:i') ?? '-' }}</td>
                </tr>
                @php
                    $slot = $booking->convertedSlot;
                @endphp

                @if($slot && $slot->arrival_time)
                <tr>
                    <td class="vb-table__label">Actual Arrival</td>
                    <td class="vb-table__value--strong">{{ $slot->arrival_time->format('d-m-Y H:i') }}</td>
                </tr>
                <tr>
                    <td class="vb-table__label">Arrival Status</td>
                    <td>
                        @php
                            $arrivalDiff = (int) round($booking->planned_start->diffInMinutes($slot->arrival_time, false));
                            if($arrivalDiff > 15) {
                                $arrivalStatus = 'Late';
                                $arrivalColor = 'danger';
                            } elseif($arrivalDiff < -15) {
                                $arrivalStatus = 'On-Time (Early)';
                                $arrivalColor = 'success';
                            } else {
                                $arrivalStatus = 'On-Time';
                                $arrivalColor = 'success';
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

                @if($slot && $slot->actual_start)
                <tr>
                    <td class="vb-table__label">Actual Start</td>
                    <td class="vb-table__value--strong">{{ $slot->actual_start->format('d-m-Y H:i') }}</td>
                </tr>
                @endif

                @if($slot && $slot->actual_finish)
                <tr>
                    <td class="vb-table__label">Actual Complete</td>
                    <td class="vb-table__value--strong">{{ $slot->actual_finish->format('d-m-Y H:i') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="vb-table__label">Requested At</td>
                    <td>{{ $booking->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
                @if($booking->approved_at)
                <tr>
                    <td class="vb-table__label">Processed At</td>
                    <td>{{ $booking->approved_at->format('d-m-Y H:i') }}</td>
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
            @if($booking->status === 'approved' && ($booking->converted_slot_id || $booking->convertedSlot))
            <a href="{{ route('vendor.bookings.ticket', ['slotId' => $booking->converted_slot_id ?? $booking->convertedSlot->id]) }}" 
               class="vendor-btn vendor-btn--primary" 
               target="_blank">
                <i class="fas fa-print"></i>
                Print Ticket
            </a>
            @endif

            <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>

            @if($booking->status === 'pending' && auth()->user()->hasRole('vendor'))
                <button type="button"
                        class="vendor-btn vendor-btn--danger"
                        onclick="openVendorCancelModal('{{ route('vendor.bookings.cancel', $booking->id) }}', '{{ $booking->request_number ?? ('REQ-' . $booking->id) }}')">
                    <i class="fas fa-times"></i>
                    Cancel Booking
                </button>
            @endif
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.ajaxReload = function(pushState) {
            if (window.__isLoadingAjax) return;
            window.__isLoadingAjax = true;
            
            var container = document.querySelector('.vendor-card');

            var url = window.location.href;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(res) { return res.text(); })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newContainer = doc.querySelector('.vendor-card');
                var currentContainer = document.querySelector('.vendor-card');
                
                if (currentContainer && newContainer) {
                    currentContainer.innerHTML = newContainer.innerHTML;
                }
            })
            .finally(function() {
                window.__isLoadingAjax = false;
            });
        };
    });
</script>
@endpush
@endsection
