@extends('vendor.layouts.vendor')

@section('title', 'Vendor Self-Service Portal')

@section('content')
<!-- Welcome & Stats Section -->
<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">
        Welcome back, {{ auth()->user()->full_name }}
    </h1>
    <p style="color: #64748b;">Manage your slot bookings directly from this dashboard.</p>
</div>

@if(isset($actionRequired) && $actionRequired->count() > 0)
    <div class="vendor-alert vendor-alert--warning">
        <div>
            <strong>Action Required:</strong>
            You have {{ $actionRequired->count() }} booking(s) that require your attention.
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                @foreach($actionRequired as $req)
                    <li>
                        Booking #{{ $req->ticket_number }} is 
                        @if($req->status == 'pending_vendor_confirmation')
                            pending your confirmation (Rescheduled).
                        @else
                            {{ str_replace('_', ' ', $req->status) }}.
                        @endif
                        <a href="{{ route('vendor.bookings.show', $req->id) }}" style="text-decoration: underline; font-weight: 600;">View</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
    
    <!-- LEFT COLUMN: NEW BOOKING -->
    <div class="vendor-card" style="position: sticky; top: 1rem;">
        <div class="vendor-card__header">
            <h2 class="vendor-card__title">
                <i class="fas fa-plus-circle" style="color: #3b82f6;"></i>
                Create Booking
            </h2>
        </div>

        <div style="color:#64748b; font-size: 0.95rem; line-height: 1.5; margin-bottom: 1rem;">
            Use the full booking form to submit documents (COA/SJ) and select PO items/quantity.
        </div>

        <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary" style="width: 100%; justify-content: center;">
            <i class="fas fa-plus"></i>
            Create New Booking
        </a>
    </div>

    <!-- RIGHT COLUMN: MY BOOKINGS -->
    <div>
        <div class="vendor-card">
            <div class="vendor-card__header">
                <h2 class="vendor-card__title">
                    <i class="fas fa-list" style="color: #64748b;"></i>
                    Recent Bookings
                </h2>
                <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">View All</a>
            </div>

            @if($recentBookings->count() > 0)
                <div class="vendor-table-wrapper">
                    <table class="vendor-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBookings as $booking)
                                <tr>
                                    <td style="font-weight: 600;">{{ $booking->ticket_number }}</td>
                                    <td>
                                        <div>{{ $booking->planned_start->format('d M Y') }}</div>
                                        <small style="color: #64748b;">{{ $booking->planned_start->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $badges = [
                                                'pending_approval' => 'pending_approval',
                                                'scheduled' => 'secondary',
                                                'rejected' => 'danger',
                                                'completed' => 'success',
                                                'pending_vendor_confirmation' => 'warning',
                                                'cancelled' => 'secondary'
                                            ];
                                            $badge = $badges[$booking->status] ?? 'secondary';
                                            $label = str_replace('_', ' ', ucfirst($booking->status));
                                            if($booking->status == 'pending_vendor_confirmation') $label = 'Action Needed';
                                        @endphp
                                        <span class="vendor-badge vendor-badge--{{ $badge }}">{{ $label }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 2rem; color: #94a3b8;">
                    <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                    <p>No Booking History Found.</p>
                </div>
            @endif
        </div>

        
    </div>
</div>
@endsection
