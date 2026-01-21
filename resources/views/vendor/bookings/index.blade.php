@extends('vendor.layouts.vendor')

@section('title', 'My Bookings - Vendor Portal')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-calendar-days"></i>
            My Bookings
        </h1>
        <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary">
            <i class="fas fa-plus"></i>
            New Booking
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('vendor.bookings.index') }}" style="margin-bottom: 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div class="vendor-form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="vendor-form-label">Status</label>
                <select name="status" class="vendor-form-select">
                    <option value="">All Statuses</option>
                    <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                    <option value="pending_vendor_confirmation" {{ request('status') === 'pending_vendor_confirmation' ? 'selected' : '' }}>Needs Confirmation</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="arrived" {{ request('status') === 'arrived' ? 'selected' : '' }}>Arrived</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            
            <div class="vendor-form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                <label class="vendor-form-label">From Date</label>
                <input type="date" name="date_from" class="vendor-form-input" value="{{ request('date_from') }}">
            </div>
            
            <div class="vendor-form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                <label class="vendor-form-label">To Date</label>
                <input type="date" name="date_to" class="vendor-form-input" value="{{ request('date_to') }}">
            </div>
            
            <div class="vendor-form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="vendor-form-label">Search</label>
                <input type="text" name="search" class="vendor-form-input" placeholder="Ticket or Vehicle..." value="{{ request('search') }}">
            </div>
            
            <button type="submit" class="vendor-btn vendor-btn--primary">
                <i class="fas fa-search"></i>
                Filter
            </button>
            
            @if(request()->hasAny(['status', 'date_from', 'date_to', 'search']))
            <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">
                <i class="fas fa-times"></i>
                Clear
            </a>
            @endif
        </div>
    </form>

    <!-- Bookings Table -->
    @if($bookings->count() > 0)
    <div style="overflow-x: auto;">
        <table class="vendor-table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Scheduled Time</th>
                    <th>Duration</th>
                    <th>Gate</th>
                    <th>Direction</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                <tr>
                    <td>
                        <strong>{{ $booking->ticket_number }}</strong>
                        @if($booking->vehicle_number_snap)
                        <br><small style="color: #64748b;">{{ $booking->vehicle_number_snap }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $booking->planned_start?->format('d M Y') ?? '-' }}
                        <br><small style="color: #64748b;">{{ $booking->planned_start?->format('H:i') ?? '' }}</small>
                    </td>
                    <td>
                        {{ $booking->planned_duration }} Min
                    </td>
                    <td>
                        {{ $booking->plannedGate?->name ?? '-' }}
                    </td>
                    <td>
                        <span class="vendor-badge vendor-badge--{{ $booking->direction === 'inbound' ? 'info' : 'warning' }}">
                            <i class="fas fa-{{ $booking->direction === 'inbound' ? 'arrow-down' : 'arrow-up' }}"></i>
                            {{ ucfirst($booking->direction) }}
                        </span>
                    </td>
                    <td>
                        <span class="vendor-badge vendor-badge--{{ $booking->status_badge_color }}">
                            {{ $booking->status_label }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            @if($booking->status === 'pending_vendor_confirmation')
                            <a href="{{ route('vendor.bookings.confirm', $booking->id) }}" class="vendor-btn vendor-btn--primary vendor-btn--sm" title="Confirm">
                                <i class="fas fa-check"></i>
                            </a>
                            @endif
                            
                            @if(in_array($booking->status, ['pending_approval', 'scheduled', 'pending_vendor_confirmation']))
                            <form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                @csrf
                                <input type="hidden" name="reason" value="Cancelled by vendor">
                                <button type="submit" class="vendor-btn vendor-btn--danger vendor-btn--sm" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 1.5rem;">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @else
    <div style="text-align: center; padding: 3rem; color: #64748b;">
        <i class="fas fa-calendar-xmark" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
        <p>No Bookings Found.</p>
        <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary" style="margin-top: 1rem;">
            <i class="fas fa-plus"></i>
            Create Booking
        </a>
    </div>
    @endif
</div>
@endsection
