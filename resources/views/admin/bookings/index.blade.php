@extends('layouts.app')

@section('title', 'Booking Requests')
@section('page_title', 'Booking Requests')

@section('content')
<div class="st-card">
    <!-- Stats Tabs -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="{{ route('bookings.index', ['status' => 'pending']) }}"
           class="st-stat-tab {{ $status === 'pending' ? 'st-stat-tab--active' : '' }}">
            <span class="st-stat-tab__count">{{ $counts['pending'] ?? 0 }}</span>
            <span class="st-stat-tab__label">Pending</span>
        </a>
        <a href="{{ route('bookings.index', ['status' => 'approved']) }}"
           class="st-stat-tab {{ $status === 'approved' ? 'st-stat-tab--active' : '' }}">
            <span class="st-stat-tab__count">{{ $counts['approved'] ?? 0 }}</span>
            <span class="st-stat-tab__label">Approved</span>
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
                <label class="st-label">From Date</label>
                <input type="text" name="date_from" class="st-input" value="{{ request('date_from') }}" id="date_from_filter" placeholder="Select Date">
            </div>

            <div class="st-filter-group">
                <label class="st-label">To Date</label>
                <input type="text" name="date_to" class="st-input" value="{{ request('date_to') }}" id="date_to_filter" placeholder="Select Date">
            </div>

            <div class="st-filter-group">
                <label class="st-label">Search</label>
                <input type="text" name="search" class="st-input" placeholder="Request No, PO, Supplier, Requester..." value="{{ request('search') }}">
            </div>

            <div class="st-filter-actions" style="display:flex;justify-content:flex-end;">
                @if(request()->hasAny(['date_from', 'date_to', 'search']))
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
                    <th>Request</th>
                    <th>PO</th>
                    <th>Supplier</th>
                    <th>Requested By</th>
                    <th>COA</th>
                    <th>Surat Jalan</th>
                    <th>Scheduled</th>
                    <th>Converted Ticket</th>
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
                            <strong>{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</strong>
                        </a>
                    </td>
                    <td>{{ $booking->po_number ?? '-' }}</td>
                    <td>{{ $booking->supplier_name ?? '-' }}</td>
                    <td>{{ $booking->requester?->full_name ?? '-' }}</td>
                    <td>
                        @if(!empty($booking->coa_path))
                            <a href="{{ asset('storage/' . $booking->coa_path) }}" target="_blank" rel="noopener">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if(!empty($booking->surat_jalan_path))
                            <a href="{{ asset('storage/' . $booking->surat_jalan_path) }}" target="_blank" rel="noopener">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        {{ $booking->planned_start?->format('d M Y') ?? '-' }}
                        <br><small class="st-text-muted">{{ $booking->planned_start?->format('H:i') ?? '' }}</small>
                    </td>
                    <td>{{ $booking->convertedSlot?->ticket_number ?? '-' }}</td>
                    <td>{{ $booking->convertedSlot?->plannedGate?->name ?? 'TBD' }}</td>
                    <td>
                        <span class="st-badge st-badge--{{ $booking->direction }}">
                            {{ ucfirst($booking->direction) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $badgeColor = match($booking->status) {
                                'pending' => 'pending_approval',
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
                        <span class="st-badge st-badge--{{ $badgeColor }}">{{ $badgeLabel }}</span>
                    </td>
                    <td>
                        {{ $booking->created_at?->format('d M Y H:i') ?? '-' }}
                    </td>
                    <td>
                        <div class="st-action-buttons">
                            <a href="{{ route('bookings.show', $booking->id) }}" class="st-button st-button--sm st-button--secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if($booking->status === 'pending')
                            @can('bookings.approve')
                            <a href="{{ route('bookings.show', $booking->id) }}" class="st-button st-button--sm st-button--success" title="Approve">
                                <i class="fas fa-check"></i>
                            </a>
                            @endcan

                            @can('bookings.reschedule')
                            <a href="{{ route('bookings.reschedule', $booking->id) }}" class="st-button st-button--sm st-button--warning" title="Reschedule">
                                <i class="fas fa-calendar-alt"></i>
                            </a>
                            @endcan

                            @can('bookings.reject')
                            <button type="button" class="st-button st-button--sm st-button--danger" title="Reject"
                                    onclick="openRejectModal({{ $booking->id }}, '{{ $booking->request_number ?? ('REQ-' . $booking->id) }}')">
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
        <p>No Booking Requests Found.</p>
    </div>
    @endif
</div>

<!-- Custom Confirmation Modal -->
<div id="approveModal" class="st-custom-modal">
    <div class="st-custom-modal-overlay" onclick="closeApproveModal()"></div>
    <div class="st-custom-modal-container">
        <div class="st-custom-modal-header">
            <h3>Confirm Approval</h3>
            <button type="button" class="st-custom-modal-close" onclick="closeApproveModal()">&times;</button>
        </div>
        <form id="approveForm" method="POST" action="">
            @csrf
            <div class="st-custom-modal-body text-center">
                <p>Are you sure you want to approve booking <strong id="modalTicketNumber"></strong>?</p>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-button st-button--success">Yes, Approve</button>
                <button type="button" class="st-button st-button--secondary" onclick="closeApproveModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="st-custom-modal">
    <div class="st-custom-modal-overlay" onclick="closeRejectModal()"></div>
    <div class="st-custom-modal-container">
        <div class="st-custom-modal-header">
            <h3>Reject Booking</h3>
            <button type="button" class="st-custom-modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="st-custom-modal-body">
                <p>Are you sure you want to reject booking <strong id="reject-ticket"></strong>?</p>
                <div class="st-form-group" style="margin-top: 15px;">
                    <label class="st-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Reason for Rejection <span class="st-required">*</span></label>
                    <textarea name="reason" class="st-textarea" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit;" rows="3" required placeholder="Please Provide a Reason for Rejection..."></textarea>
                </div>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-btn st-btn--primary" style="background-color: #dc2626; border-color: #dc2626; color: #fff;">Reject Booking</button>
                <button type="button" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);" onclick="closeRejectModal()">Cancel</button>
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

</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateFrom = document.getElementById('date_from_filter');
    var dateTo = document.getElementById('date_to_filter');
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function initFilterFlatpickr(el) {
        if (!el) return;
        window.flatpickr(el, {
            dateFormat: 'Y-m-d',
            disableMobile: true,
            allowInput: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                if (holidayData[dateStr]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = holidayData[dateStr];
                }
            }
        });
    }

    initFilterFlatpickr(dateFrom);
    initFilterFlatpickr(dateTo);

    // Auto-submit form on input change
    const bookingFilterForm = document.getElementById('booking-filter-form');
    if (bookingFilterForm) {
        // Auto-submit on input with debounce for text inputs
        const textInputs = bookingFilterForm.querySelectorAll('input[type="text"]');
        textInputs.forEach(function(input) {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    bookingFilterForm.submit();
                }, 500); // 500ms debounce
            });

            // Submit on Enter key
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(timeout);
                    bookingFilterForm.submit();
                }
            });
        });
    }
});

function openRejectModal(bookingId, ticketNumber) {
    const modal = document.getElementById('reject-modal');
    document.getElementById('reject-ticket').textContent = ticketNumber;
    document.getElementById('reject-form').action = '/bookings/' + bookingId + '/reject';
    modal.classList.add('active');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.remove('active');
}

function openApproveModal(id, ticket) {
    const modal = document.getElementById('approveModal');
    const ticketSpan = document.getElementById('modalTicketNumber');
    const form = document.getElementById('approveForm');

    ticketSpan.innerText = ticket;
    form.action = "{{ url('/bookings') }}/" + id + "/approve";
    modal.classList.add('active');
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('active');
}
</script>
@endpush
