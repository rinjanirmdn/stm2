@extends('vendor.layouts.vendor')

@section('title', 'Booking Detail - Vendor Portal')

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
            <div style="margin-top: 0.5rem;">
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
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
        <div>
            <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
                <i class="fas fa-info-circle"></i>
                Booking Information
            </h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b; width: 40%;">Ticket Number</td>
                    <td style="padding: 0.5rem 0; font-weight: 600;">{{ $booking->convertedSlot?->ticket_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Status</td>
                    <td style="padding: 0.5rem 0;">
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
                    <td style="padding: 0.5rem 0; color: #64748b;">Direction</td>
                    <td style="padding: 0.5rem 0;">
                        <span class="vendor-badge vendor-badge--{{ $booking->direction === 'inbound' ? 'info' : 'warning' }}">
                            <i class="fas fa-{{ $booking->direction === 'inbound' ? 'arrow-down' : 'arrow-up' }}"></i>
                            {{ ucfirst($booking->direction) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Warehouse</td>
                    <td style="padding: 0.5rem 0;">
                        @php
                            $whCode = $booking->convertedSlot?->warehouse?->wh_code ?? null;
                            $whName = $booking->convertedSlot?->warehouse?->wh_name ?? ($booking->convertedSlot?->warehouse?->name ?? null);
                        @endphp
                        @if(!empty($whCode) || !empty($whName))
                            {{ trim(($whCode ? ($whCode . ' - ') : '') . ($whName ?? '')) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Gate</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->convertedSlot?->plannedGate?->gate_number ?? ($booking->convertedSlot?->plannedGate?->name ?? 'To be assigned') }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">COA</td>
                    <td style="padding: 0.5rem 0;">
                        @if(!empty($booking->coa_path))
                            <a href="{{ asset('storage/' . $booking->coa_path) }}" target="_blank" rel="noopener">View / Download</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Surat Jalan</td>
                    <td style="padding: 0.5rem 0;">
                        @if(!empty($booking->surat_jalan_path))
                            <a href="{{ asset('storage/' . $booking->surat_jalan_path) }}" target="_blank" rel="noopener">View / Download</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">PO Number</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->po_number ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div>
            <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
                <i class="fas fa-clock"></i>
                Schedule
            </h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b; width: 40%;">Scheduled Date</td>
                    <td style="padding: 0.5rem 0; font-weight: 600;">{{ $booking->planned_start?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Scheduled Time</td>
                    <td style="padding: 0.5rem 0; font-weight: 600;">{{ $booking->planned_start?->format('H:i') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Duration</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->planned_duration }} Minutes</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Requested At</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->created_at?->format('d M Y H:i') ?? '-' }}</td>
                </tr>
                @if($booking->approved_at)
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Processed At</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->approved_at->format('d M Y H:i') }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div>
            <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
                <i class="fas fa-truck"></i>
                Vehicle Information
            </h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b; width: 40%;">Truck Type</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->truck_type ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Vehicle Number</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->vehicle_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Driver Number</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->driver_number ?? '-' }}</td>
                </tr>
            </table>
        </div>

        @if($booking->approval_notes || $booking->approver)
        <div>
            <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
                <i class="fas fa-user-check"></i>
                Approval Info
            </h3>
            
            <table style="width: 100%;">
                @if($booking->approver)
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b; width: 40%;">Processed By</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->approver->full_name }}</td>
                </tr>
                @endif
                @if($booking->approval_notes)
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Notes</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->approval_notes }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
    </div>

    <!-- Actions -->
    @if(in_array($booking->status, ['pending', 'approved']))
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
        <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
            <i class="fas fa-cogs"></i>
            Actions
        </h3>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            @if(!empty($booking->convertedSlot?->ticket_number))
            <a href="{{ route('vendor.bookings.ticket', $booking->convertedSlot->id) }}" class="vendor-btn vendor-btn--secondary" target="_blank">
                <i class="fas fa-print"></i>
                Print Ticket
            </a>
            @endif
            
            @if($booking->status === 'pending')
                <form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}" 
                      onsubmit="return confirm('Are you sure you want to cancel this booking?');" style="display: inline;">
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
