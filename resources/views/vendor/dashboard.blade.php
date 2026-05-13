@extends('vendor.layouts.vendor')

@section('title', 'Dashboard - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--dashboard')

@section('content')

    <!-- Status Strip (Clickable) -->
    @if(isset($stats))
        <div class="vd-container">
            <div class="vd-scroll-container">
                <div class="vd-content-container">
                    <!-- Status Cards -->
                    <div class="vd-status-strip"
                        title="Summary of your latest booking statuses (from planned & booking requests).">
                        <a href="{{ route('vendor.bookings.index', ['status' => 'scheduled', 'date_from' => $rangeStart, 'date_to' => $rangeEnd]) }}"
                            class="vd-status-item vd-status-item--scheduled"
                            title="Scheduled bookings (approved and not yet arrived).">
                            <div class="vd-status-item__count">{{ $stats['scheduled'] ?? 0 }}</div>
                            <div class="vd-status-item__label">Scheduled</div>
                        </a>
                        <a href="{{ route('vendor.bookings.index', ['status' => 'waiting', 'date_from' => $rangeStart, 'date_to' => $rangeEnd]) }}" class="vd-status-item vd-status-item--waiting"
                            title="Truck arrived, waiting in queue.">
                            <div class="vd-status-item__count">{{ $stats['waiting'] ?? 0 }}</div>
                            <div class="vd-status-item__label">Waiting</div>
                        </a>
                        <a href="{{ route('vendor.bookings.index', ['status' => 'in_progress', 'date_from' => $rangeStart, 'date_to' => $rangeEnd]) }}" class="vd-status-item vd-status-item--inprogress"
                            title="Loading/Unloading in progress.">
                            <div class="vd-status-item__count">{{ $stats['in_progress'] ?? 0 }}</div>
                            <div class="vd-status-item__label">In progress</div>
                        </a>
                        <a href="{{ route('vendor.bookings.index', ['status' => 'completed', 'date_from' => $rangeStart, 'date_to' => $rangeEnd]) }}" class="vd-status-item vd-status-item--completed"
                            title="Process finished.">
                            <div class="vd-status-item__count">{{ $stats['completed'] ?? 0 }}</div>
                            <div class="vd-status-item__label">Completed</div>
                        </a>
                        <a href="{{ route('vendor.bookings.index', ['date_from' => $rangeStart, 'date_to' => $rangeEnd]) }}" class="vd-status-item vd-status-item--total"
                            title="Total bookings.">
                            <div class="vd-status-item__count">{{ $stats['total'] ?? 0 }}</div>
                            <div class="vd-status-item__label">Total</div>
                        </a>
                    </div>

                    <!-- Global Date Range Filter for all charts -->
                    <div class="vd-top-filters">
                        <form method="GET" action="{{ route('vendor.dashboard') }}" class="vd-date-filter-form">
                            <input type="hidden" name="range_start" id="vd-range-start"
                                value="{{ request('range_start', now()->startOfMonth()->format('Y-m-d')) }}">
                            <input type="hidden" name="range_end" id="vd-range-end"
                                value="{{ request('range_end', now()->endOfMonth()->format('Y-m-d')) }}">
                            <input type="hidden" name="date_range" id="vd-date-range"
                                value="{{ request('date_range', 'this_month') }}">

                            <div id="vd_reportrange" class="date-range-input vd-range-picker" data-auto-submit="true">
                                <div class="date-range-input__left">
                                    <i class="fas fa-calendar date-range-icon vendor-icon"></i>
                                    <span></span>
                                </div>
                                <i class="fa fa-caret-down"></i>
                            </div>
                            <a href="{{ route('vendor.dashboard') }}" class="vd-date-reset-btn">
                                Reset
                            </a>
                        </form>
                    </div>

                    <!-- 3 Containers Section -->
                    <div class="vd-charts-section">
                        <!-- Container 1: Bar Chart -->
                        <div class="vd-chart-card">
                            <div class="vd-chart-header">
                                <h3 class="vd-chart-title">
                                    <i class="fas fa-chart-bar vd-icon"></i>
                                    Status Overview
                                    <i class="fas fa-info-circle vendor-info-pin"
                                        title="Shows counts per status (Scheduled, Waiting, In Progress, Completed) based on your planned in the selected date range."></i>
                                </h3>
                            </div>
                            <div class="vd-chart-body">
                                <script type="application/json"
                                    id="vendor-status-overview-data">{!! json_encode(['stats' => $stats]) !!}</script>
                                <div id="vendor-status-overview-react" class="w-full"></div>
                            </div>
                        </div>

                        <!-- Container 2: On Time vs Late Pie Chart -->
                        <div class="vd-chart-card vd-chart-card--ontime">
                            <div class="vd-chart-header">
                                <h3 class="vd-chart-title">
                                    <i class="fas fa-chart-pie vd-icon"></i>
                                    On Time vs Late
                                    <i class="fas fa-info-circle vendor-info-pin"
                                        title="Shows on-time vs late arrival ratio for your completed data this month. A truck is considered late if it arrives more than 15 minutes after the scheduled time."></i>
                                </h3>
                            </div>
                            <div class="vd-chart-body vd-chart-body--pie">
                                <script type="application/json"
                                    id="vendor-ontime-data">{!! json_encode($performance ?? []) !!}</script>
                                <div id="vendor-ontime-react" class="w-full"></div>
                            </div>
                        </div>

                        <!-- Container 3: Recent Bookings -->
                        <div class="vd-recent-card">
                            <div class="vd-chart-header">
                                <h3 class="vd-chart-title">
                                    <i class="fas fa-clock-rotate-left vd-icon"></i>
                                    Recent Bookings
                                    <i class="fas fa-info-circle vendor-info-pin"
                                        title="Latest booking requests and their live status (including converted tickets)."></i>
                                </h3>
                                <div class="vd-recent-filters">
                                    @php
                                        $arrivalFilter = $arrivalFilter ?? request('arrival_filter', '');
                                        $baseParams = [
                                            'range_start' => $rangeStart ?? request('range_start'),
                                            'range_end' => $rangeEnd ?? request('range_end'),
                                        ];
                                    @endphp
                                    <div class="vd-recent-filter-group">
                                        <select id="vd-arrival-filter"
                                            style="font-size:0.8em;padding:3px 6px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#334155;max-width:160px">
                                            <option value="">All Arrival</option>
                                            <option value="ontime">On Time</option>
                                            <option value="late">Late</option>
                                        </select>
                                    </div>
                                    @if($isInternalVendor ?? false)
                                        <div class="vd-recent-filter-group">
                                            <select
                                                id="vd-vendor-filter-select"
                                                class="vendor-searchable-select"
                                                style="width: 160px;">
                                                <option value="">All Vendors</option>
                                                @foreach(($vendorNames ?? []) as $vn)
                                                    <option value="{{ $vn }}" {{ ($vendorFilter ?? '') === $vn ? 'selected' : '' }}>
                                                        {{ $vn }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <a href="{{ route('vendor.bookings.index') }}" class="vd-view-all-link">View All</a>
                                </div>
                            </div>
                            <div class="vd-recent-body">
                                @if($recentBookings->count() > 0)
                                    @foreach($recentBookings as $booking)
                                        @php
                                            // Show real booking status when booking has been converted to a slot
                                            $slot = $booking->convertedSlot;
                                            $displayStatus = $slot ? $slot->status : $booking->status;
                                            $arrivalLabel = null;
                                            $arrivalBadge = null;
                                            if ($slot && $slot->planned_start && $slot->arrival_time) {
                                                $diffMin = $slot->planned_start->diffInMinutes($slot->arrival_time, false);
                                                if ($diffMin > 15) {
                                                    $arrivalLabel = 'Late';
                                                    $arrivalBadge = 'danger';
                                                } else {
                                                    $arrivalLabel = 'On Time';
                                                    $arrivalBadge = 'success';
                                                }
                                            }
                                            $displayLabel = match ($displayStatus) {
                                                'pending' => 'Pending',
                                                'approved' => 'Scheduled',
                                                'scheduled' => 'Scheduled',
                                                'waiting' => 'Waiting',
                                                'in_progress' => 'In Progress',
                                                'completed' => 'Completed',
                                                'rejected' => 'Rejected',
                                                'cancelled' => 'Cancelled',
                                                default => ucfirst(str_replace('_', ' ', $displayStatus)),
                                            };
                                            $badgeColor = match ($displayStatus) {
                                                'pending' => 'warning',
                                                'approved' => 'secondary',
                                                'scheduled' => 'secondary',
                                                'waiting' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'cancelled',
                                                default => 'secondary',
                                            };
                                            $ticketLabel = $slot ? ($slot->ticket_number ?? $booking->request_number ?? 'REQ-' . $booking->id) : ($booking->request_number ?? 'REQ-' . $booking->id);
                                        @endphp
                                        <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vd-recent-item"
                                            data-arrival="{{ $arrivalLabel ? strtolower($arrivalLabel === 'On Time' ? 'ontime' : 'late') : '' }}"
                                            data-vendor="{{ $booking->supplier_name ?? '' }}">
                                            <div class="vd-recent-main">
                                                <span class="vd-recent-ticket">{{ $ticketLabel }}</span>
                                                @if($booking->po_number)
                                                    <span style="font-size:0.78em;color:#475569;font-weight:500">
                                                        {{ $booking->po_number }}
                                                    </span>
                                                @endif
                                                <span class="vd-recent-time">{{ $booking->planned_start->format('d-m-Y H:i') }}</span>
                                            </div>
                                            <div class="vd-recent-meta">
                                                @if(($isInternalVendor ?? false) && $booking->supplier_name)
                                                    <span
                                                        style="font-size:0.78em;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                        title="{{ $booking->supplier_name }}">
                                                        <i class="fas fa-building"
                                                            style="margin-right:2px;opacity:0.5"></i>{{ $booking->supplier_name }}
                                                    </span>
                                                @endif
                                                <span
                                                    class="vendor-badge vendor-badge--{{ $badgeColor }} vd-badge--xs">{{ $displayLabel }}</span>
                                                @if($arrivalLabel)
                                                    <span
                                                        class="vendor-badge vendor-badge--{{ $arrivalBadge }} vd-badge--xs">{{ $arrivalLabel }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                @else
                                    <div class="vd-recent-empty">
                                        <i class="fas fa-calendar-xmark"></i>
                                        <span>No recent bookings</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 vendor filter — match arrival dropdown style */
        .vd-recent-filters .select2-container {
            font-size: 0.8em;
        }
        .vd-recent-filters .select2-container--default .select2-selection--single {
            height: 28px !important;
            min-height: 28px !important;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            display: flex;
            align-items: center;
            position: relative;
        }
        .vd-recent-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            padding-left: 6px !important;
            padding-right: 20px !important;
            color: #334155 !important;
            font-size: 1em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .vd-recent-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 26px !important;
            right: 2px;
        }
        .vd-recent-filters .select2-container--default .select2-selection--single .select2-selection__clear {
            font-size: 1.1em;
            color: #94a3b8;
            margin-right: 0;
            font-weight: 400;
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            line-height: 1;
        }
        .vd-recent-filters .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: #ef4444;
        }
        .select2-dropdown {
            font-size: 0.8em;
            border-color: #e2e8f0;
            border-radius: 6px;
        }
        /* Fix grid restrictions that push cards out of bounds on laptops */
        .vendor-page--dashboard .vd-charts-section {
            max-height: none !important;
            grid-template-rows: auto !important;
        }

        /* Prevent CSS Grid collapse bug for spanning flex items */
        .vendor-page--dashboard .vd-chart-card,
        .vendor-page--dashboard .vd-recent-card {
            min-height: 360px !important;
        }
        
        /* Ensure React containers take up remaining space */
        #vendor-status-overview-react,
        #vendor-ontime-react {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 240px !important;
        }
        .vendor-page--dashboard .vd-chart-body {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 !important;
        }
    </style>
@endpush

@push('scripts')
    @vite(['resources/js/pages/vendor.js'])
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // --- Client-side Recent Bookings filters (no page reload) ---
            var MAX_VISIBLE = 5;

            function applyRecentFilters() {
                var arrivalVal = $('#vd-arrival-filter').val() || '';
                var vendorVal = '';
                if ($('#vd-vendor-filter-select').length > 0) {
                    vendorVal = ($('#vd-vendor-filter-select').val() || '').trim();
                }

                var items = $('.vd-recent-item');
                var shown = 0;
                items.each(function() {
                    var el = $(this);
                    var itemArrival = (el.attr('data-arrival') || '').toLowerCase();
                    var itemVendor = (el.attr('data-vendor') || '');

                    var matchArrival = !arrivalVal || itemArrival === arrivalVal;
                    var matchVendor = !vendorVal || itemVendor === vendorVal;

                    if (matchArrival && matchVendor && shown < MAX_VISIBLE) {
                        el.show();
                        shown++;
                    } else {
                        el.hide();
                    }
                });

                // Show/hide empty state
                var emptyEl = $('.vd-recent-empty');
                if (shown === 0 && items.length > 0) {
                    if (emptyEl.length === 0) {
                        $('.vd-recent-body').append('<div class="vd-recent-empty vd-recent-empty--filter"><i class="fas fa-filter"></i><span>No matches for filter</span></div>');
                    } else {
                        emptyEl.show();
                    }
                } else {
                    $('.vd-recent-empty--filter').remove();
                }
            }

            // Arrival dropdown
            $('#vd-arrival-filter').on('change', function() {
                applyRecentFilters();
            });

            // Vendor Select2 dropdown
            if ($('#vd-vendor-filter-select').length > 0) {
                $('#vd-vendor-filter-select').select2({
                    width: '160px',
                    placeholder: 'All Vendors',
                    allowClear: true
                });
                
                $('#vd-vendor-filter-select').on('select2:select select2:unselect', function () {
                    applyRecentFilters();
                });
            }

            // Apply initial filter (show first 5)
            applyRecentFilters();
        });
    </script>
@endpush