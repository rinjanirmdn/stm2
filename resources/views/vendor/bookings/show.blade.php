@extends('vendor.layouts.vendor')

@section('title', 'Booking Detail - Vendor Portal')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-ticket"></i>
            Booking {{ $booking->ticket_number }}
        </h1>
        <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <!-- Status Banner -->
    @if($booking->status === 'pending_approval')
    <div class="vendor-alert vendor-alert--warning">
        <i class="fas fa-clock"></i>
        <div>
            <strong>Pending Approval</strong> - Your booking request is waiting for admin approval.
        </div>
    </div>
    @elseif($booking->status === 'pending_vendor_confirmation')
    <div class="vendor-alert vendor-alert--info">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <strong>Action Required</strong> - Admin has rescheduled your booking. Please confirm or reject the new schedule.
            <div style="margin-top: 0.5rem;">
                <a href="{{ route('vendor.bookings.confirm', $booking->id) }}" class="vendor-btn vendor-btn--primary vendor-btn--sm">
                    <i class="fas fa-check"></i>
                    Review & Confirm
                </a>
            </div>
        </div>
    </div>
    @elseif($booking->status === 'cancelled')
    <div class="vendor-alert vendor-alert--error">
        <i class="fas fa-times-circle"></i>
        <div>
            <strong>Cancelled</strong> - {{ $booking->cancelled_reason ?? $booking->approval_notes ?? 'Your booking was cancelled.' }}
            <div style="margin-top: 0.5rem;">
                <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary vendor-btn--sm">
                    <i class="fas fa-redo"></i>
                    Create New Booking
                </a>
            </div>
        </div>
    </div>
    @elseif($booking->status === 'scheduled')
    <div class="vendor-alert vendor-alert--success">
        <i class="fas fa-check-circle"></i>
        <strong>Confirmed</strong> - Your booking has been approved and scheduled.
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
                    <td style="padding: 0.5rem 0; font-weight: 600;">{{ $booking->ticket_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Status</td>
                    <td style="padding: 0.5rem 0;">
                        <span class="vendor-badge vendor-badge--{{ $booking->status_badge_color }}">
                            {{ $booking->status_label }}
                        </span>
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
                            $whCode = $booking->warehouse?->wh_code ?? null;
                            $whName = $booking->warehouse?->wh_name ?? ($booking->warehouse?->name ?? null);
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
                    <td style="padding: 0.5rem 0;">{{ $booking->plannedGate?->gate_number ?? ($booking->plannedGate?->name ?? 'To be assigned') }}</td>
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
                    <td style="padding: 0.5rem 0;">{{ $booking->planned_duration }} minutes</td>
                </tr>
                @if($booking->original_planned_start && $booking->original_planned_start != $booking->planned_start)
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Original Request</td>
                    <td style="padding: 0.5rem 0; text-decoration: line-through; color: #94a3b8;">
                        {{ $booking->original_planned_start?->format('d M Y H:i') }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Requested At</td>
                    <td style="padding: 0.5rem 0;">{{ $booking->requested_at?->format('d M Y H:i') ?? '-' }}</td>
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
                    <td style="padding: 0.5rem 0;">{{ $booking->vehicle_number_snap ?? '-' }}</td>
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
                @if($booking->approval_action)
                <tr>
                    <td style="padding: 0.5rem 0; color: #64748b;">Action</td>
                    <td style="padding: 0.5rem 0;">{{ ucfirst($booking->approval_action) }}</td>
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
    @if(in_array($booking->status, ['pending_approval', 'scheduled', 'pending_vendor_confirmation']))
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
        <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
            <i class="fas fa-cogs"></i>
            Actions
        </h3>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            @if(!empty($booking->ticket_number))
            <a href="{{ route('vendor.bookings.ticket', $booking->id) }}" class="vendor-btn vendor-btn--secondary" target="_blank">
                <i class="fas fa-print"></i>
                Print Ticket
            </a>
            @endif

            @if($booking->status === 'pending_vendor_confirmation')
            <a href="{{ route('vendor.bookings.confirm', $booking->id) }}" class="vendor-btn vendor-btn--primary">
                <i class="fas fa-check"></i>
                Confirm
            </a>

            <a href="{{ route('vendor.bookings.confirm', $booking->id) }}#propose" class="vendor-btn vendor-btn--secondary">
                <i class="fas fa-calendar-alt"></i>
                Propose Another Schedule
            </a>
            @endif
            
            <form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}" 
                  onsubmit="return confirm('Are you sure you want to cancel this booking?');" style="display: inline;">
                @csrf
                <input type="hidden" name="reason" value="Cancelled by vendor">
                <button type="submit" class="vendor-btn vendor-btn--danger">
                    <i class="fas fa-times"></i>
                    Cancel Booking
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

<!-- Booking History -->
@if($booking->bookingHistories && $booking->bookingHistories->count() > 0)
<div class="vendor-card">
    <div class="vendor-card__header">
        <h2 class="vendor-card__title">
            <i class="fas fa-history"></i>
            Booking History
        </h2>
    </div>
    
    <div style="position: relative; padding-left: 2rem;">
        @foreach($booking->bookingHistories as $history)
        <div style="position: relative; padding-bottom: 1.5rem; border-left: 2px solid #e5e7eb; padding-left: 1.5rem; margin-left: -2rem;">
            <div style="position: absolute; left: -0.5rem; top: 0; width: 1rem; height: 1rem; background: white; border: 2px solid #3b82f6; border-radius: 50%;"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span class="vendor-badge vendor-badge--{{ $history->action_badge_color }}">
                        {{ $history->action_label }}
                    </span>
                    <span style="color: #64748b; font-size: 0.875rem; margin-left: 0.5rem;">
                        by {{ $history->performer?->full_name ?? 'System' }}
                    </span>
                </div>
                <span style="color: #94a3b8; font-size: 0.75rem;">
                    {{ $history->created_at->format('d M Y H:i') }}
                </span>
            </div>
            
            @if($history->notes)
            <p style="margin: 0.5rem 0 0; color: #475569; font-size: 0.875rem;">
                {{ $history->notes }}
            </p>
            @endif
            
            @if($history->old_planned_start || $history->new_planned_start)
            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #64748b;">
                @if($history->old_planned_start)
                <span style="text-decoration: line-through;">{{ \Carbon\Carbon::parse($history->old_planned_start)->format('d M Y H:i') }}</span>
                @endif
                @if($history->new_planned_start)
                <span> → {{ \Carbon\Carbon::parse($history->new_planned_start)->format('d M Y H:i') }}</span>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
