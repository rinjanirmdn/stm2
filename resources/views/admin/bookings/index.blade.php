@extends('layouts.app')

@section('title', 'Booking Requests')
@section('page_title', 'Booking Requests')

@section('content')
    <!-- Combined Filter and Stats Card -->
    <div class="st-card" style="margin-bottom:2px;font-size:12px;">
        <div style="padding:2px;">
            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: space-between;">
                <!-- Stats Tabs Section (Left) -->
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    <a href="{{ route('bookings.index', ['status' => 'pending']) }}"
                       class="st-stat-tab {{ $status === 'pending' ? 'st-stat-tab--active' : '' }}" style="font-size:12px;padding:4px 8px;">
                        <span class="st-stat-tab__count" style="font-size:12px;">{{ $counts['pending'] ?? 0 }}</span>
                        <span class="st-stat-tab__label" style="font-size:12px;">Pending</span>
                    </a>
                    <a href="{{ route('bookings.index', ['status' => 'approved']) }}"
                       class="st-stat-tab {{ $status === 'approved' ? 'st-stat-tab--active' : '' }}" style="font-size:12px;padding:4px 8px;">
                        <span class="st-stat-tab__count" style="font-size:12px;">{{ $counts['approved'] ?? 0 }}</span>
                        <span class="st-stat-tab__label" style="font-size:12px;">Approved</span>
                    </a>
                </div>

                <!-- Filter Section (Right) -->
                <form method="GET" action="{{ route('bookings.index') }}" style="flex: 1;">
                    <div class="st-form-row" style="gap:2px;align-items:center;justify-content:flex-end;">
                        <div class="st-form-field" style="max-width:140px;">
                            <label class="st-label" style="font-size:12px;">Search</label>
                            <input type="text" name="search" class="st-input" style="font-size:12px;padding:2px 4px;height:24px;" placeholder="Request No, PO..." value="{{ request('search') }}">
                        </div>
                        <div class="st-form-field" style="max-width:80px;">
                            <label class="st-label" style="font-size:12px;">From</label>
                            <input type="text" name="date_from" class="st-input" style="font-size:12px;padding:2px 4px;height:24px;" value="{{ request('date_from') }}" id="date_from_filter" placeholder="Select Date">
                        </div>
                        <div class="st-form-field" style="max-width:80px;">
                            <label class="st-label" style="font-size:12px;">To</label>
                            <input type="text" name="date_to" class="st-input" style="font-size:12px;padding:2px 4px;height:24px;" value="{{ request('date_to') }}" id="date_to_filter" placeholder="Select Date">
                        </div>
                        <div class="st-form-field" style="min-width:40px;flex:0 0 auto;display:flex;justify-content:flex-end;gap:2px;">
                            @if(request()->hasAny(['date_from', 'date_to', 'search']))
                            <a href="{{ route('bookings.index', ['status' => $status]) }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);font-size:12px;padding:2px 6px;height:24px;">Reset</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;position:relative;">
                <form method="GET" id="booking-filter-form" data-multi-sort="1" action="{{ route('bookings.index') }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    @php
                        $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                        $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                    @endphp
                    @foreach ($sortsArr as $i => $s)
                        @php $d = $dirsArr[$i] ?? 'asc'; @endphp
                        <input type="hidden" name="sort[]" value="{{ $s }}">
                        <input type="hidden" name="dir[]" value="{{ $d }}">
                    @endforeach
                    <div class="st-table-wrapper" style="min-height: 400px;">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>Request</th>
                                    <th>PO</th>
                                    <th>Supplier</th>
                                    <th>Requested By</th>
                                    <th>COA</th>
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
                                @if($bookings->count() > 0)
                                @foreach($bookings as $booking)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
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
                                            <a href="{{ asset('storage/' . $booking->coa_path) }}" target="_blank" rel="noopener" style="color: #3b82f6; text-decoration: underline;">View</a>
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
                                        <div class="st-action-dropdown">
                                            <button type="button" class="st-btn st-btn--ghost st-action-trigger" style="padding:4px 8px;font-size:16px;line-height:1;border:none;color:#6b7280;">
                                                &#x22ee;
                                            </button>
                                            <div class="st-action-menu">
                                                <a href="{{ route('bookings.show', $booking->id) }}" class="st-action-item">View</a>

                                                @if($booking->status === 'pending')
                                                @can('bookings.approve')
                                                <a href="{{ route('bookings.show', $booking->id) }}" class="st-action-item">Approve</a>
                                                @endcan

                                                @can('bookings.reject')
                                                <button type="button" class="st-action-item st-action-item--danger" onclick="openRejectModal({{ $booking->id }}, '{{ $booking->request_number ?? ('REQ-' . $booking->id) }}')">
                                                    Reject
                                                </button>
                                                @endcan

                                                @can('bookings.reschedule')
                                                <a href="{{ route('bookings.reschedule', $booking->id) }}" class="st-action-item">Reschedule</a>
                                                @endcan
                                                @endif
                                            </div>
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
                </form>
            </div>
        </div>
    </section>

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
                <button type="submit" class="st-btn st-btn--success">Yes, Approve</button>
                <button type="button" class="st-btn st-btn--secondary" onclick="closeApproveModal()">Cancel</button>
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
                <button type="submit" class="st-btn st-btn--danger">Reject Booking</button>
                <button type="button" class="st-btn st-btn--secondary" onclick="closeRejectModal()">Cancel</button>
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

/* Action Dropdown Styles */
.st-action-dropdown {
    position: relative;
    display: inline-block;
}

.st-action-menu {
    position: absolute;
    right: 0;
    top: 100%;
    z-index: 1000;
    min-width: 120px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    display: none;
    padding: 4px 0;
}

.st-action-menu.show {
    display: block;
}

.st-action-item {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 12px;
    color: #374151;
    text-decoration: none;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

.st-action-item:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.st-action-item--danger {
    color: #dc2626;
}

.st-action-item--danger:hover {
    background-color: #fef2f2;
    color: #b91c1c;
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

// Action Menu Toggle
document.addEventListener('click', function(e) {
    if (e.target.closest('.st-action-trigger')) {
        e.preventDefault();
        e.stopPropagation();
        const trigger = e.target.closest('.st-action-trigger');
        const menu = trigger.nextElementSibling;

        // Close all other open menus
        document.querySelectorAll('.st-action-menu.show').forEach(m => {
            if (m !== menu) m.classList.remove('show');
        });

        menu.classList.toggle('show');
    } else {
        // Click outside, close all
        document.querySelectorAll('.st-action-menu.show').forEach(m => {
            m.classList.remove('show');
        });
    }
});

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
