@extends('layouts.app')

@section('title', 'Booking Requests')
@section('page_title', 'Booking Requests')

@section('content')
    <!-- Filter & Summary Row -->
    <div class="st-card st-mb-2 st-text-12">
        <div class="st-p-2">
            <div class="st-form-row st-gap-8 st-items-end">
                <div class="st-form-field st-flex-1">
                    <label class="st-label st-text-12">Booking Summary</label>
                    <div class="st-flex st-gap-10 st-w-full">
                        <div class="st-booking-summary-pill st-booking-summary-pill--pending">
                            <span class="st-booking-summary-pill__label">Pending</span>
                            <span class="st-booking-summary-pill__value">{{ $counts['pending'] ?? 0 }}</span>
                        </div>
                        <div class="st-booking-summary-pill st-booking-summary-pill--approved">
                            <span class="st-booking-summary-pill__label">Approved</span>
                            <span class="st-booking-summary-pill__value">{{ $counts['approved'] ?? 0 }}</span>
                        </div>
                        <div class="st-booking-summary-pill st-booking-summary-pill--all">
                            <span class="st-booking-summary-pill__label">All</span>
                            <span class="st-booking-summary-pill__value">{{ $counts['all'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="st-form-field st-maxw-220">
                    <label class="st-label st-text-12">Search</label>
                    <input type="text" name="search" form="booking-filter-form" class="st-input st-text-12 st-px-4 st-py-2" placeholder="Request No, PO..." value="{{ request('search') }}">
                </div>
                <div class="st-form-field st-maxw-220 st-relative">
                    <label class="st-label st-text-12">Date Range</label>
                    <div id="booking_reportrange" class="st-dashboard-range-picker">
                        <span>Select range</span>
                    </div>
                    <input type="hidden" name="date_from" id="date_from" form="booking-filter-form" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="date_to" form="booking-filter-form" value="{{ request('date_to') }}">
                </div>
                <div class="st-form-field st-minw-80 st-flex-0 st-flex st-justify-end">
                    @if(request()->hasAny(['date_from', 'date_to', 'search', 'request_number', 'po_number', 'supplier_name', 'planned_start', 'converted_ticket', 'planned_gate_id', 'direction', 'status_filter', 'created_at']))
                    <a href="{{ route('bookings.index', ['status' => $status]) }}" class="st-btn st-btn--outline-primary st-text-12 st-py-2 st-px-10 st-h-24">Reset</a>
                    @endif
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
                        $showConvertedTicket = in_array($status, ['approved', 'all'], true) || request('status_filter') === 'approved';
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
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="request_number" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="request_number" title="Filter">â·</button>
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
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po_number" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="po_number" title="Filter">â·</button>
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
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="supplier_name" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="supplier_name" title="Filter">â·</button>
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
                                    <th class="st-table-col-90">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">COA</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="coa" title="Sort">â‡…</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Scheduled</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="planned_start" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="planned_start" title="Filter">â·</button>
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
                                    @if($showConvertedTicket)
                                        <th>
                                            <div class="st-colhead">
                                                <span class="st-colhead__label">Ticket</span>
                                                <span class="st-colhead__icons">
                                                    <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="converted_ticket" title="Sort">â‡…</button>
                                                    <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="converted_ticket" title="Filter">â·</button>
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
                                    @endif
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Gate</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="gate" title="Sort">â‡…</button>
                                                @if($showConvertedTicket)
                                                    <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="planned_gate_id" title="Filter">â·</button>
                                                @endif
                                            </span>
                                            @if($showConvertedTicket)
                                                <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="planned_gate_id">
                                                    <div class="st-font-semibold st-mb-6">Gate Filter</div>
                                                    <select name="planned_gate_id" form="booking-filter-form" class="st-select">
                                                        <option value="">All</option>
                                                        @foreach(($gateOptions ?? []) as $opt)
                                                            <option value="{{ $opt['id'] }}" {{ (string)request('planned_gate_id') === (string)$opt['id'] ? 'selected' : '' }}>
                                                                {{ $opt['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="st-panel__actions">
                                                        <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="planned_gate_id">Clear</button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="st-table-col-90">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Direction</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="direction" title="Filter">â·</button>
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
                                    <th class="st-table-col-120">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Status</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="status" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="status" title="Filter">â·</button>
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
                                    <th class="st-table-col-150">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Requested At</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="requested_at" data-type="date" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="requested_at" title="Filter">â·</button>
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
                                                    <a href="{{ asset('storage/' . $booking->coa_path) }}" target="_blank" rel="noopener" class="st-link st-link-underline">View</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ $booking->planned_start?->format('d M Y') ?? '-' }}
                                                <br><small class="st-text-muted">{{ $booking->planned_start?->format('H:i') ?? '' }}</small>
                                            </td>
                                            @if($showConvertedTicket)
                                                <td>{{ $booking->convertedSlot?->ticket_number ?? '-' }}</td>
                                            @endif
                                            <td>{{ $booking->convertedSlot?->plannedGate?->name ?? 'TBD' }}</td>
                                            <td class="st-td-center">
                                                <span class="st-badge st-badge--{{ $booking->direction }}">
                                                    {{ ucfirst($booking->direction) }}
                                                </span>
                                            </td>
                                            <td class="st-td-center">
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
                                            <td class="st-td-center">{{ $booking->created_at?->format('d M Y H:i') ?? '-' }}</td>
                                            <td class="st-td-center">
                                                <div class="st-action-dropdown">
                                                    <button type="button" class="st-btn st-btn--ghost st-action-trigger st-action-trigger--compact">
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
                                @else
                                    <tr>
                                        <td colspan="{{ $showConvertedTicket ? 12 : 11 }}" class="st-table-empty st-text-center st-text--muted st-table-empty--roomy">No Booking Requests Found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="st-pagination">
                        {{ $bookings->withQueryString()->links() }}
                    </div>
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
                <div class="st-form-field st-mt-4">
                    <label class="st-label">Reason for Rejection <span class="st-required">*</span></label>
                    <textarea name="reason" class="st-textarea" rows="3" required placeholder="Please Provide a Reason for Rejection..."></textarea>
                </div>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-btn st-btn--danger">Reject Booking</button>
                <button type="button" class="st-btn st-btn--secondary" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="application/json" id="admin_bookings_index_config">{!! json_encode([
    'bookingsBaseUrl' => url('/bookings'),
]) !!}</script>
@vite(['resources/js/pages/admin-bookings-index.js'])
@endpush

