@extends('layouts.app')

@section('title', 'Booking Requests')
@section('page_title', 'Booking Requests')

@section('content')
<div class="st-card">
    <!-- Stats Tabs -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="{{ route('bookings.index', ['status' => 'pending_approval']) }}" 
           class="st-stat-tab {{ $status === 'pending_approval' ? 'st-stat-tab--active' : '' }}">
            <span class="st-stat-tab__count">{{ $counts['pending_approval'] }}</span>
            <span class="st-stat-tab__label">Pending Approval</span>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'pending_vendor_confirmation']) }}" 
           class="st-stat-tab {{ $status === 'pending_vendor_confirmation' ? 'st-stat-tab--active' : '' }}">
            <span class="st-stat-tab__count">{{ $counts['pending_vendor'] }}</span>
            <span class="st-stat-tab__label">Awaiting Vendor</span>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'scheduled']) }}" 
           class="st-stat-tab {{ $status === 'scheduled' ? 'st-stat-tab--active' : '' }}">
            <span class="st-stat-tab__count">{{ $counts['scheduled'] }}</span>
            <span class="st-stat-tab__label">Scheduled</span>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'all']) }}" 
           class="st-stat-tab {{ $status === 'all' ? 'st-stat-tab--active' : '' }}">
            <span class="st-stat-tab__label">All Requests</span>
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('bookings.index') }}" class="st-filter-form">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="st-filter-row">
            <div class="st-filter-group">
                <label class="st-label">Warehouse</label>
                <select name="warehouse_id" class="st-select">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                            {{ $wh->wh_code }} - {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="st-filter-group">
                <label class="st-label">From Date</label>
                <input type="date" name="date_from" class="st-input" value="{{ request('date_from') }}">
            </div>
            
            <div class="st-filter-group">
                <label class="st-label">To Date</label>
                <input type="date" name="date_to" class="st-input" value="{{ request('date_to') }}">
            </div>
            
            <div class="st-filter-group">
                <label class="st-label">Search</label>
                <input type="text" name="search" class="st-input" placeholder="Ticket, vendor, requester..." value="{{ request('search') }}">
            </div>
            
            <div class="st-filter-actions">
                <button type="submit" class="st-button st-button--primary">
                    <i class="fas fa-search"></i>
                    Filter
                </button>
                @if(request()->hasAny(['warehouse_id', 'date_from', 'date_to', 'search']))
                <a href="{{ route('bookings.index', ['status' => $status]) }}" class="st-button st-button--secondary">
                    <i class="fas fa-times"></i>
                    Clear
                </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bookings Table -->
    @if($bookings->count() > 0)
    <div class="st-table-responsive">
        <table class="st-table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Vendor</th>
                    <th>Requested By</th>
                    <th>Scheduled</th>
                    <th>Gate</th>
                    <th>Direction</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                <tr>
                    <td>
                        <a href="{{ route('bookings.show', $booking->id) }}" class="st-link">
                            <strong>{{ $booking->ticket_number }}</strong>
                        </a>
                    </td>
                    <td>{{ $booking->vendor?->name ?? '-' }}</td>
                    <td>{{ $booking->requester?->full_name ?? '-' }}</td>
                    <td>
                        {{ $booking->planned_start?->format('d M Y') ?? '-' }}
                        <br><small class="st-text-muted">{{ $booking->planned_start?->format('H:i') ?? '' }}</small>
                    </td>
                    <td>{{ $booking->plannedGate?->name ?? 'TBD' }}</td>
                    <td>
                        <span class="st-badge st-badge--{{ $booking->direction === 'inbound' ? 'info' : 'warning' }}">
                            {{ ucfirst($booking->direction) }}
                        </span>
                    </td>
                    <td>
                        <span class="st-badge st-badge--{{ $booking->status_badge_color }}">
                            {{ $booking->status_label }}
                        </span>
                    </td>
                    <td>
                        {{ $booking->requested_at?->format('d M Y H:i') ?? '-' }}
                    </td>
                    <td>
                        <div class="st-action-buttons">
                            <a href="{{ route('bookings.show', $booking->id) }}" class="st-button st-button--sm st-button--secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            @if($booking->status === 'pending_approval')
                            @can('bookings.approve')
                            <form method="POST" action="{{ route('bookings.approve', $booking->id) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="st-button st-button--sm st-button--success" title="Approve" onclick="return confirm('Approve this booking?')">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endcan
                            
                            @can('bookings.reschedule')
                            <a href="{{ route('bookings.reschedule', $booking->id) }}" class="st-button st-button--sm st-button--warning" title="Reschedule">
                                <i class="fas fa-calendar-alt"></i>
                            </a>
                            @endcan
                            
                            @can('bookings.reject')
                            <button type="button" class="st-button st-button--sm st-button--danger" title="Reject" 
                                    onclick="openRejectModal({{ $booking->id }}, '{{ $booking->ticket_number }}')">
                                <i class="fas fa-times"></i>
                            </button>
                            @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="st-pagination">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @else
    <div class="st-empty-state">
        <i class="fas fa-inbox"></i>
        <p>No booking requests found.</p>
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="st-modal" style="display: none;">
    <div class="st-modal__backdrop" onclick="closeRejectModal()"></div>
    <div class="st-modal__content">
        <div class="st-modal__header">
            <h3>Reject Booking</h3>
            <button type="button" class="st-modal__close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="st-modal__body">
                <p>Are you sure you want to reject booking <strong id="reject-ticket"></strong>?</p>
                <div class="st-form-group">
                    <label class="st-label">Reason <span class="st-required">*</span></label>
                    <textarea name="reason" class="st-textarea" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                </div>
            </div>
            <div class="st-modal__footer">
                <button type="button" class="st-button st-button--secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="st-button st-button--danger">Reject Booking</button>
            </div>
        </form>
    </div>
</div>

<style>
.st-stat-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--st-surface, #fff);
    border: 2px solid var(--st-border, #e5e7eb);
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.st-stat-tab:hover {
    border-color: var(--st-primary, #3b82f6);
    transform: translateY(-2px);
}

.st-stat-tab--active {
    border-color: var(--st-primary, #3b82f6);
    background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(59,130,246,0.05) 100%);
}

.st-stat-tab__count {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--st-primary, #3b82f6);
}

.st-stat-tab__label {
    font-size: 0.75rem;
    color: var(--st-text-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.st-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.st-modal__backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.st-modal__content {
    position: relative;
    background: var(--st-surface, #fff);
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.st-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--st-border, #e5e7eb);
}

.st-modal__header h3 {
    margin: 0;
    font-size: 1.125rem;
}

.st-modal__close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--st-text-muted, #64748b);
}

.st-modal__body {
    padding: 1.5rem;
}

.st-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--st-border, #e5e7eb);
}
</style>

@endsection

@push('scripts')
<script>
function openRejectModal(bookingId, ticketNumber) {
    document.getElementById('reject-modal').style.display = 'flex';
    document.getElementById('reject-ticket').textContent = ticketNumber;
    document.getElementById('reject-form').action = '/bookings/' + bookingId + '/reject';
}

function closeRejectModal() {
    document.getElementById('reject-modal').style.display = 'none';
}
</script>
@endpush
