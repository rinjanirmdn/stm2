@extends('layouts.app')

@section('title', 'Booking Requests')
@section('page_title', 'Booking Requests')

@section('content')
    <!-- Combined Filter and Stats Card -->
    <div class="st-card st-mb-2 st-text-12">
        <div class="st-p-2">
            <div class="st-flex st-gap-8 st-align-center st-justify-between">
                <!-- Stats Tabs Section (Left) -->
                <div class="st-flex st-gap-8 st-flex-wrap st-align-center">
                    <a href="{{ route('bookings.index', ['status' => 'pending']) }}"
                       class="st-stat-tab {{ $status === 'pending' ? 'st-stat-tab--active' : '' }} st-text-12 st-px-8 st-py-4">
                        <span class="st-stat-tab__count st-text-12">{{ $counts['pending'] ?? 0 }}</span>
                        <span class="st-stat-tab__label st-text-12">Pending</span>
                    </a>
                    <a href="{{ route('bookings.index', ['status' => 'approved']) }}"
                       class="st-stat-tab {{ $status === 'approved' ? 'st-stat-tab--active' : '' }} st-text-12 st-px-8 st-py-4">
                        <span class="st-stat-tab__count st-text-12">{{ $counts['approved'] ?? 0 }}</span>
                        <span class="st-stat-tab__label st-text-12">Approved</span>
                    </a>
                </div>

                <!-- Filter Section (Right) -->
                <div class="st-flex-1">
                    <div class="st-form-row st-gap-8 st-items-end st-justify-end">
                        <div class="st-form-field st-maxw-220">
                            <label class="st-label st-text-12">Search</label>
                            <input type="text" name="search" form="booking-filter-form" class="st-input st-text-12 st-px-4 st-py-2" placeholder="Request No, PO..." value="{{ request('search') }}">
                        </div>
                        <div class="st-form-field st-maxw-240">
                            <label class="st-label st-text-12">Date Range</label>
                            <input type="text" id="date_range_filter" class="st-input st-text-12 st-px-4 st-py-2" placeholder="Select Date Range" data-st-range-init="1" autocomplete="off" readonly>
                            <input type="hidden" name="date_from" id="date_from_filter" form="booking-filter-form" value="{{ request('date_from') }}">
                            <input type="hidden" name="date_to" id="date_to_filter" form="booking-filter-form" value="{{ request('date_to') }}">
                        </div>
                        <div class="st-form-field st-minw-80 st-flex-0 st-flex st-justify-end">
                            @if(request()->hasAny(['date_from', 'date_to', 'search', 'request_number', 'po_number', 'supplier_name', 'coa', 'planned_start', 'converted_ticket', 'gate', 'direction', 'status_filter', 'created_at']))
                            <a href="{{ route('bookings.index', ['status' => $status]) }}" class="st-btn st-btn--outline-primary st-text-12 st-py-2 st-px-10 st-h-24">Reset</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col">
            <div class="st-card st-mb-0 st-flex st-flex-col st-relative st-flex-1">
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
                    <div class="st-table-wrapper st-table-wrapper--minh-400">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th class="st-table-col-60">#</th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Request</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="request_number" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="request_number" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="request_number">
                                                <div class="st-font-semibold st-mb-6">Request Filter</div>
                                                <input type="text" name="request_number" form="booking-filter-form" class="st-input" placeholder="Search Request..." value="{{ request('request_number') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="request_number">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">PO</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po_number" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="po_number" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="po_number">
                                                <div class="st-font-semibold st-mb-6">PO Filter</div>
                                                <input type="text" name="po_number" form="booking-filter-form" class="st-input" placeholder="Search PO..." value="{{ request('po_number') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="po_number">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Supplier</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="supplier_name" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="supplier_name" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="supplier_name">
                                                <div class="st-font-semibold st-mb-6">Supplier Filter</div>
                                                <input type="text" name="supplier_name" form="booking-filter-form" class="st-input" placeholder="Search Supplier..." value="{{ request('supplier_name') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="supplier_name">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th style="width:80px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">COA</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="coa" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="coa" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-filter-panel="coa">
                                                <div class="st-font-semibold st-mb-6">COA Filter</div>
                                                <select name="coa" form="booking-filter-form" class="st-select">
                                                    <option value="">All</option>
                                                    <option value="1" {{ request('coa') === '1' ? 'selected' : '' }}>Has COA</option>
                                                    <option value="0" {{ request('coa') === '0' ? 'selected' : '' }}>No COA</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="coa">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Scheduled</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="planned_start" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="planned_start" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-220 st-maxh-220" data-filter-panel="planned_start">
                                                <div class="st-font-semibold st-mb-6">Scheduled Filter</div>
                                                <input type="date" name="planned_start" form="booking-filter-form" class="st-input" value="{{ request('planned_start') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="planned_start">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Converted Ticket</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="converted_ticket" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="converted_ticket" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="converted_ticket">
                                                <div class="st-font-semibold st-mb-6">Ticket Filter</div>
                                                <input type="text" name="converted_ticket" form="booking-filter-form" class="st-input" placeholder="Search Ticket..." value="{{ request('converted_ticket') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="converted_ticket">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Gate</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="gate" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="gate" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="gate">
                                                <div class="st-font-semibold st-mb-6">Gate Filter</div>
                                                <input type="text" name="gate" form="booking-filter-form" class="st-input" placeholder="Search Gate..." value="{{ request('gate') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="gate">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th style="width:95px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Direction</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="direction" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-filter-panel="direction">
                                                <div class="st-font-semibold st-mb-6">Direction Filter</div>
                                                <select name="direction" form="booking-filter-form" class="st-select">
                                                    <option value="">All</option>
                                                    <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                                                    <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="direction">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th style="width:120px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Status</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="status" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="status_filter" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-220 st-maxh-220" data-filter-panel="status_filter">
                                                <div class="st-font-semibold st-mb-6">Status Filter</div>
                                                <select name="status_filter" form="booking-filter-form" class="st-select">
                                                    <option value="">All</option>
                                                    <option value="pending" {{ request('status_filter') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="approved" {{ request('status_filter') === 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="rejected" {{ request('status_filter') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    <option value="cancelled" {{ request('status_filter') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="status_filter">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th style="width:150px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Requested At</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="created_at" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="created_at" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-220 st-maxh-220" data-filter-panel="created_at">
                                                <div class="st-font-semibold st-mb-6">Requested At Filter</div>
                                                <input type="date" name="created_at" form="booking-filter-form" class="st-input" value="{{ request('created_at') }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="created_at">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
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
    var rangeInput = document.getElementById('date_range_filter');
    var dateFrom = document.getElementById('date_from_filter');
    var dateTo = document.getElementById('date_to_filter');
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function applyDatepickerTooltips(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);

        dp.find('td.is-holiday').each(function() {
            const cell = window.jQuery(this);
            const dayText = cell.find('a, span').first().text();
            if (!dayText) return;
            const fallbackYear = inst.drawYear ?? inst.selectedYear;
            const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
            const year = cell.data('year') ?? fallbackYear;
            const month = cell.data('month') ?? fallbackMonth;
            if (year === undefined || month === undefined) return;
            const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
            const title = holidayData[ds] || '';
            if (title) {
                cell.attr('data-st-tooltip', title);
                cell.find('a, span').attr('data-st-tooltip', title);
            }
            cell.removeAttr('title');
            cell.find('a, span').removeAttr('title');
        });
    }

    function bindDatepickerHover(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);
        let hideTimer = null;
        let tooltip = document.getElementById('st-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'st-datepicker-tooltip';
            tooltip.className = 'st-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
        dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
            const text = window.jQuery(this).attr('data-st-tooltip') || '';
            if (!text) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            tooltip.textContent = text;
            tooltip.classList.add('st-datepicker-tooltip--visible');
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
            hideTimer = setTimeout(function() {
                tooltip.classList.remove('st-datepicker-tooltip--visible');
            }, 300);
        });
    }

    function initFilterDatepicker(el) {
        if (!el) return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
        if (el.getAttribute('data-st-datepicker') === '1') return;
        el.setAttribute('data-st-datepicker', '1');

        window.jQuery(el).datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShowDay: function(date) {
                const ds = toIsoDate(date);
                if (holidayData[ds]) {
                    return [true, 'is-holiday', holidayData[ds]];
                }
                return [true, '', ''];
            },
            beforeShow: function(input, inst) {
                setTimeout(function() {
                    applyDatepickerTooltips(inst);
                    bindDatepickerHover(inst);
                }, 0);
            },
            onChangeMonthYear: function(year, month, inst) {
                setTimeout(function() {
                    applyDatepickerTooltips(inst);
                    bindDatepickerHover(inst);
                }, 0);
            }
        });

        const inst = window.jQuery(el).data('datepicker');
        if (inst) {
            applyDatepickerTooltips(inst);
            bindDatepickerHover(inst);
        }
    }

    function applyDaterangepickerTooltips(picker) {
        if (!picker || !picker.container) return;
        const container = picker.container;
        window.jQuery(container).find('td.is-holiday').each(function() {
            const cell = window.jQuery(this);
            const dataTitle = cell.attr('data-title') || '';
            const match = dataTitle.match(/^r(\d+)c(\d+)$/);
            if (!match) return;
            const r = parseInt(match[1], 10);
            const c = parseInt(match[2], 10);
            const calEl = cell.closest('.drp-calendar');
            const isLeft = calEl.hasClass('left');
            const cal = isLeft ? picker.leftCalendar : picker.rightCalendar;
            if (!cal || !Array.isArray(cal.calendar) || !cal.calendar[r] || !cal.calendar[r][c]) return;
            const m = cal.calendar[r][c];
            const ds = (m && typeof m.format === 'function') ? m.format('YYYY-MM-DD') : '';
            const title = ds && holidayData[ds] ? holidayData[ds] : '';
            if (!title) return;
            cell.attr('data-st-tooltip', title);
            cell.find('span').attr('data-st-tooltip', title);
            cell.removeAttr('title');
            cell.find('span').removeAttr('title');
        });
    }

    function bindDaterangepickerHover(picker) {
        if (!picker || !picker.container) return;
        const dp = window.jQuery(picker.container);
        let hideTimer = null;
        let tooltip = document.getElementById('st-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'st-datepicker-tooltip';
            tooltip.className = 'st-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
        dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
            const text = window.jQuery(this).attr('data-st-tooltip') || '';
            if (!text) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            tooltip.textContent = text;
            tooltip.classList.add('st-datepicker-tooltip--visible');
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
            hideTimer = setTimeout(function() {
                tooltip.classList.remove('st-datepicker-tooltip--visible');
            }, 300);
        });
    }

    function initRangePicker() {
        if (!rangeInput || !dateFrom || !dateTo) return;

        var startVal = dateFrom.value || '';
        var endVal = dateTo.value || '';
        if (startVal && endVal) {
            rangeInput.value = startVal + ' - ' + endVal;
        } else if (startVal) {
            rangeInput.value = startVal + ' - ' + startVal;
        }

        if (window.jQuery && typeof window.jQuery.fn.daterangepicker !== 'undefined' && typeof window.moment !== 'undefined') {
            var startDate = startVal ? window.moment(startVal) : window.moment();
            var endDate = endVal ? window.moment(endVal) : startDate;

            window.jQuery(rangeInput)
                .daterangepicker({
                    startDate: startDate,
                    endDate: endDate,
                    autoUpdateInput: true,
                    locale: { format: 'YYYY-MM-DD' },
                    isCustomDate: function(date) {
                        var ds = date.format('YYYY-MM-DD');
                        if (holidayData[ds]) return 'is-holiday';
                        return '';
                    }
                }, function(start, end) {
                    var startStr = start.format('YYYY-MM-DD');
                    var endStr = end.format('YYYY-MM-DD');
                    dateFrom.value = startStr;
                    dateTo.value = endStr;
                    rangeInput.value = startStr + ' - ' + endStr;
                    var form = document.getElementById('booking-filter-form');
                    if (form) form.submit();
                })
                .on('show.daterangepicker', function() {
                    var picker = window.jQuery(rangeInput).data('daterangepicker');
                    if (!picker) return;
                    setTimeout(function() {
                        applyDaterangepickerTooltips(picker);
                        bindDaterangepickerHover(picker);
                    }, 0);
                })
                .on('showCalendar.daterangepicker', function() {
                    var picker = window.jQuery(rangeInput).data('daterangepicker');
                    if (!picker) return;
                    setTimeout(function() {
                        applyDaterangepickerTooltips(picker);
                        bindDaterangepickerHover(picker);
                    }, 0);
                });

            var picker = window.jQuery(rangeInput).data('daterangepicker');
            if (picker) {
                setTimeout(function() {
                    applyDaterangepickerTooltips(picker);
                    bindDaterangepickerHover(picker);
                }, 0);
            }

            return;
        }

        if (window.jQuery && window.jQuery.fn.datepicker) {
            initFilterDatepicker(rangeInput);
            window.jQuery(rangeInput).datepicker('option', 'onSelect', function(dateText) {
                dateFrom.value = dateText;
                dateTo.value = dateText;
                rangeInput.value = dateText + ' - ' + dateText;
                var form = document.getElementById('booking-filter-form');
                if (form) form.submit();
            });
        }
    }

    initRangePicker();

    // Auto-submit form on input change
    const bookingFilterForm = document.getElementById('booking-filter-form');
    if (bookingFilterForm) {
        // Auto-submit on input with debounce for text inputs
        const textInputs = bookingFilterForm.querySelectorAll('input[type="text"][name="search"]');
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
