@extends('layouts.app')

@section('title', 'Slots - Slot Time Management')
@section('page_title', 'Slots')
@section('body_class', 'st-page--slots')

@section('content')
    <!-- Custom Reject Booking Dialog -->
    <div id="customConfirmDialog" class="st-dialog st-dialog--overlay st-hidden">
        <div class="st-card st-dialog__card">
            <div class="st-card__header st-dialog__header">
                <h3 class="st-dialog__title">Cancel Booking</h3>
            </div>
            <div class="st-card__body st-dialog__body">
                <form id="cancel-booking-form" method="POST" action="">
                    @csrf
                    <p id="rejectConfirmationText" class="st-dialog__text">
                        Are you sure you want to cancel booking <span id="slotNumber" class="st-font-semibold"></span>?
                    </p>

                    <div class="st-form-field st-dialog__field">
                        <label for="rejectReason" class="st-label st-dialog__label">
                            Reason for Cancellation
                        </label>
                        <textarea
                            id="rejectReason"
                            name="cancelled_reason"
                            class="st-input"
                            rows="4"
                            required
                            placeholder="Please Provide a Reason for Cancellation..."
                        ></textarea>
                    </div>

                    <div class="st-dialog__actions">
                        <button id="confirmRejectYes" type="submit" class="st-btn st-btn--danger st-dialog__btn">
                            CANCEL BOOKING
                        </button>
                        <button id="confirmRejectNo" type="button" class="st-btn st-btn--outline-primary st-dialog__btn">
                            BACK
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-form-row st-gap-4 st-align-end">
                        <div class="st-form-field st-maxw-260">
                            <label class="st-label">Search</label>
                            <div class="st-input-wrap">
                                <input type="text" name="q" form="slot-filter-form" class="st-input" placeholder="Truck, MAT DOC, Vendor, Etc" value="{{ $search }}">
                                <div id="slot-search-suggestions" class="st-suggestions st-hidden"></div>
                            </div>
                        </div>
                        <div class="st-form-field st-maxw-120">
                            <label class="st-label">Show</label>
                            <select name="page_size" form="slot-filter-form" class="st-select">
                                @foreach (['10','25','50','100','all'] as $ps)
                                    <option value="{{ $ps }}" {{ $pageSize === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="st-form-field st-minw-80 st-flex st-flex-0 st-justify-end st-gap-8">
                            <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary">Reset</a>
                            @unless(optional(auth()->user())->hasRole('Operator'))
                            @can('slots.create')
                            <a href="{{ route('slots.create') }}" class="st-btn st-btn--primary">Create Slot</a>
                            @endcan
                            @endunless
                        </div>
            </div>
        </div>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1">
                <form method="GET" id="slot-filter-form" action="{{ route('slots.index') }}" data-multi-sort="1" autocomplete="off">
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
                                <th class="st-w-40">#</th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">PO</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="truck" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll st-panel--z9" data-filter-panel="truck">
                                            <div class="st-panel__title">PO Filter</div>
                                            <input type="text" name="truck" form="slot-filter-form" class="st-input" placeholder="Search PO..." value="{{ $truck ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="truck">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">MAT DOC</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="mat_doc" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll st-panel--z9" data-filter-panel="mat_doc">
                                            <div class="st-panel__title">MAT DOC Filter</div>
                                            <input type="text" name="mat_doc" form="slot-filter-form" class="st-input" placeholder="Search MAT DOC..." value="{{ $mat_doc ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="mat_doc">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Vendor</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="vendor" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll st-panel--z9" data-filter-panel="vendor">
                                            <div class="st-panel__title">Vendor Filter</div>
                                            <input type="text" name="vendor" form="slot-filter-form" class="st-input" placeholder="Search Vendor..." value="{{ $vendor ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="vendor">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header st-filter-header--inline">
                                        <span>Warehouse / Gate</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse" title="Sort">â‡…</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="whgate"
                                            title="Filter Warehouse / Gate"
                                        >
                                            <span>&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel st-panel st-panel--wide-lg st-panel--scroll st-panel--z9" data-filter-panel="whgate">
                                            <div class="st-panel__title">Warehouse / Gate Filter</div>
                                            <div class="st-panel__cols">
                                                <div class="st-panel__col st-minw-140">
                                                    <div class="st-panel__label">Warehouse</div>
                                                    <select name="warehouse_id[]" form="slot-filter-form" class="st-select st-filter-warehouse-select st-select--panel">
                                                        <option value="">(All)</option>
                                                        @foreach ($warehouses as $wh)
                                                            <option value="{{ $wh->id }}" {{ in_array((string)$wh->id, array_map('strval', $warehouseFilter), true) ? 'selected' : '' }}>
                                                                {{ $wh->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="st-panel__col st-minw-100">
                                                    <div class="st-panel__label">Gate</div>
                                                    <select name="gate[]" form="slot-filter-form" class="st-select st-filter-gate-select st-select--panel">
                                                        <option value="">(All)</option>
                                                        @php $seenGates = []; @endphp
                                                        @foreach ($gates as $g)
                                                            @php
                                                                $gn = (string) ($g->gate_number ?? '');
                                                                $whId = (string) ($g->warehouse_id ?? '');
                                                                $whCode = (string) ($g->warehouse_code ?? '');
                                                            @endphp
                                                            @if ($gn !== '' && !in_array($gn, $seenGates, true))
                                                                @php $seenGates[] = $gn; @endphp
                                                                <option value="{{ $gn }}" data-warehouse-id="{{ $whId }}" {{ in_array($gn, array_map('strval', $gateFilter), true) ? 'selected' : '' }}>
                                                                    {{ $whCode }} - {{ $gn }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="whgate">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header st-filter-header--inline">
                                        <span>Direction</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" title="Sort">â‡…</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="direction"
                                            title="Filter Direction"
                                        >
                                            <span>&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel st-panel st-panel--medium st-panel--scroll st-panel--z9" data-filter-panel="direction">
                                            <div class="st-panel__title">Direction Filter</div>
                                            <select name="direction[]" form="slot-filter-form" class="st-select st-filter-direction-select st-select--panel">
                                                <option value="">(All)</option>
                                                <option value="inbound" {{ in_array('inbound', $directionFilter, true) ? 'selected' : '' }}>Inbound</option>
                                                <option value="outbound" {{ in_array('outbound', $directionFilter, true) ? 'selected' : '' }}>Outbound</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="direction">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">ETA</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="planned_start" data-type="date" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="planned_start" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide-lg st-panel--scroll st-panel--z9" data-filter-panel="planned_start">
                                            <div class="st-panel__title">ETA Range</div>
                                            <div id="eta_reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                                <i class="fa fa-calendar"></i>&nbsp;
                                                <span></span> <i class="fa fa-caret-down"></i>
                                            </div>
                                            <input type="hidden" name="date_from" id="date_from" form="slot-filter-form" value="{{ $date_from ?? '' }}">
                                            <input type="hidden" name="date_to" id="date_to" form="slot-filter-form" value="{{ $date_to ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="planned_start">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Est. Finish</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="planned_finish" data-type="date" title="Sort">â‡…</button>
                                        </span>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Arrival</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="arrival" data-type="date" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="arrival_presence" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide-lg st-panel--scroll st-panel--z9" data-filter-panel="arrival_presence">
                                            <div class="st-panel__title">Arrival Date Filter</div>
                                            <div id="arrival_reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                                <i class="fa fa-calendar"></i>&nbsp;
                                                <span></span> <i class="fa fa-caret-down"></i>
                                            </div>
                                            <input type="hidden" name="arrival_from" id="arrival_from" form="slot-filter-form" value="{{ $arrival_from ?? '' }}">
                                            <input type="hidden" name="arrival_to" id="arrival_to" form="slot-filter-form" value="{{ $arrival_to ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="arrival_presence">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Lead Time</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="lead_time" data-type="duration" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="lead_time" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll st-panel--z9" data-filter-panel="lead_time">
                                            <div class="st-panel__title">Lead Time (Min)</div>
                                            <div class="st-panel__stack">
                                                <div>
                                                    <div class="st-panel__label">Min</div>
                                                    <input type="number" name="lead_time_min" form="slot-filter-form" class="st-input" placeholder="0" value="{{ $lead_time_min ?? '' }}">
                                                </div>
                                                <div>
                                                    <div class="st-panel__label">Max</div>
                                                    <input type="number" name="lead_time_max" form="slot-filter-form" class="st-input" placeholder="999" value="{{ $lead_time_max ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="lead_time">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Target Status</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="target_status" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="target_status" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--medium st-panel--scroll st-panel--z9" data-filter-panel="target_status">
                                            <div class="st-panel__title">Target Status Filter</div>
                                            <select name="target_status[]" form="slot-filter-form" class="st-select st-select--panel">
                                                <option value="">(All)</option>
                                                <option value="achieve" {{ in_array('achieve', $targetStatusFilter ?? [], true) ? 'selected' : '' }}>Achieve</option>
                                                <option value="not_achieve" {{ in_array('not_achieve', $targetStatusFilter ?? [], true) ? 'selected' : '' }}>Not Achieve</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="target_status">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header st-filter-header--inline">
                                        <span>Arrival Status</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="late" title="Sort">â‡…</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="late"
                                            title="Filter Late"
                                        >
                                            <span>&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel st-panel st-panel--medium st-panel--scroll st-panel--z9" data-filter-panel="late">
                                            <div class="st-panel__title">Late Filter</div>
                                            <select name="late[]" form="slot-filter-form" class="st-select st-filter-late-select st-select--panel">
                                                <option value="">(All)</option>
                                                <option value="on_time" {{ in_array('on_time', $lateFilter, true) ? 'selected' : '' }}>On Time</option>
                                                <option value="late" {{ in_array('late', $lateFilter, true) ? 'selected' : '' }}>Late</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="late">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header st-filter-header--inline">
                                        <span>Status</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="status" title="Sort">â‡…</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="status"
                                            title="Filter Status"
                                        >
                                            <span>&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel st-panel st-panel--medium st-panel--scroll st-panel--z9" data-filter-panel="status">
                                            <div class="st-panel__title">Status Filter</div>
                                            <select name="status[]" form="slot-filter-form" class="st-select st-filter-status-select st-select--panel">
                                                <option value="">(All)</option>
                                                @foreach (['scheduled','waiting','in_progress','completed','cancelled'] as $st)
                                                    <option value="{{ $st }}" {{ in_array($st, $statusFilter, true) ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ', $st)) }}</option>
                                                @endforeach
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="status">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header st-filter-header--inline">
                                        <span>Blocking</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="blocking" title="Sort">â‡…</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="blocking"
                                            title="Filter Blocking"
                                        >
                                            <span>&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel st-panel st-panel--medium st-panel--scroll st-panel--z9" data-filter-panel="blocking">
                                            <div class="st-panel__title">Blocking Filter</div>
                                            <select name="blocking[]" form="slot-filter-form" class="st-select st-filter-blocking-select st-select--panel">
                                                <option value="">(All)</option>
                                                <option value="low" {{ in_array('low', $blockingFilter ?? [], true) ? 'selected' : '' }}>Low</option>
                                                <option value="medium" {{ in_array('medium', $blockingFilter ?? [], true) ? 'selected' : '' }}>Medium</option>
                                                <option value="high" {{ in_array('high', $blockingFilter ?? [], true) ? 'selected' : '' }}>High</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="blocking">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($slots as $row)
                            @php
                                $rowNumber = (int) ($loop->index ?? 0) + 1;

                                $status = trim(strtolower(strval($row->status ?? '')));
                                $isLateFlag = !empty($row->is_late);
                                $hasArrival = !empty($row->arrival_time);
                                $slotTypeVal = strval($row->slot_type ?? 'planned');

                                $fmt = function ($v) {
                                    if (empty($v)) return '-';
                                    try {
                                        return \Carbon\Carbon::parse((string) $v)->format('d M Y H:i');
                                    } catch (\Throwable $e) {
                                        return (string) $v;
                                    }
                                };

                                $plannedFinish = null;
                                if (!empty($row->planned_start) && !empty($row->planned_duration)) {
                                    try {
                                        $dt = new \DateTime((string) $row->planned_start);
                                        $dt->modify('+' . (int) $row->planned_duration . ' minutes');
                                        $plannedFinish = $dt->format('Y-m-d H:i:s');
                                    } catch (\Throwable $e) {
                                        $plannedFinish = null;
                                    }
                                }

                                $waitingMinutes = null;
                                $processMinutes = null;
                                $leadTimeMinutes = null;
                                try {
                                    $arrival = !empty($row->arrival_time) ? (string) $row->arrival_time : null;
                                    $start = !empty($row->actual_start) ? (string) $row->actual_start : null;
                                    $finish = !empty($row->actual_finish) ? (string) $row->actual_finish : null;

                                    if ($arrival && $start) {
                                        $aDt = new \DateTime($arrival);
                                        $sDt = new \DateTime($start);
                                        $diffW = $aDt->diff($sDt);
                                        $waitingMinutes = ($diffW->days * 24 * 60) + ($diffW->h * 60) + $diffW->i;
                                    }

                                    if ($start && $finish) {
                                        $sDt = new \DateTime($start);
                                        $fDt = new \DateTime($finish);
                                        $diffP = $sDt->diff($fDt);
                                        $processMinutes = ($diffP->days * 24 * 60) + ($diffP->h * 60) + $diffP->i;
                                    } elseif (!$start && $arrival && $finish) {
                                        $aDt = new \DateTime($arrival);
                                        $fDt = new \DateTime($finish);
                                        $diffP = $aDt->diff($fDt);
                                        $processMinutes = ($diffP->days * 24 * 60) + ($diffP->h * 60) + $diffP->i;
                                    }

                                    // Calculate lead time (waiting + process, or just process if no waiting)
                                    if ($waitingMinutes !== null && $processMinutes !== null) {
                                        $leadTimeMinutes = $waitingMinutes + $processMinutes;
                                    } elseif ($processMinutes !== null) {
                                        $leadTimeMinutes = $processMinutes;
                                    } elseif ($arrival && $finish) {
                                        // Fallback: calculate from arrival to finish if no start time
                                        $aDt = new \DateTime($arrival);
                                        $fDt = new \DateTime($finish);
                                        $diffL = $aDt->diff($fDt);
                                        $leadTimeMinutes = ($diffL->days * 24 * 60) + ($diffL->h * 60) + $diffL->i;
                                    }
                                } catch (\Throwable $e) {
                                    $leadTimeMinutes = null;
                                }

                                $plannedDurationMinutes = isset($row->planned_duration) ? (int) $row->planned_duration : null;
                                $targetStatus = null;
                                if ($plannedDurationMinutes !== null && $plannedDurationMinutes > 0 && $leadTimeMinutes !== null) {
                                    $threshold = $plannedDurationMinutes + 15;
                                    $targetStatus = $leadTimeMinutes <= $threshold ? 'achieve' : 'not_achieve';
                                }

                                $lateDisplay = null;
                                if (!empty($row->arrival_time) && $slotTypeVal === 'planned') {
                                    try {
                                        $a = new \DateTime((string) $row->arrival_time);
                                        $p = new \DateTime((string) $row->planned_start);
                                        $p->modify('+15 minutes');
                                        $lateDisplay = $a > $p ? 'late' : 'on_time';
                                    } catch (\Throwable $e) {
                                        $lateDisplay = null;
                                    }
                                } elseif ($status === 'completed') {
                                    $lateDisplay = $isLateFlag ? 'late' : 'on_time';
                                }

                                $blockingLevel = isset($row->blocking) ? (int) $row->blocking : 0;
                                $blockingLabel = $blockingLevel >= 2 ? 'High' : ($blockingLevel === 1 ? 'Medium' : 'Low');
                                $blockingClass = $blockingLevel >= 2 ? 'st-status-late' : ($blockingLevel === 1 ? 'st-status-processing' : 'st-status-on-time');

                                $whGateLabel = '-';
                                $gateNumber = $row->gate_number ?? '';
                                if ($gateNumber === '' || $gateNumber === null) {
                                    $whGateLabel = trim((string) ($row->warehouse_name ?? '')) !== '' ? (string) ($row->warehouse_name ?? '') : '-';
                                } else {
                                    $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName((string) ($row->warehouse_code ?? ''), (string) $gateNumber);
                                    $whGateLabel = trim(((string) ($row->warehouse_name ?? '')) !== '' ? (((string) ($row->warehouse_name ?? '')) . ' - ' . $gateLabel) : $gateLabel);
                                }

                                $badgeMap = [
                                    'scheduled' => 'bg-secondary',
                                    'arrived' => 'bg-info',
                                    'waiting' => 'bg-waiting',
                                    'in_progress' => 'bg-in_progress',
                                    'completed' => 'bg-completed',
                                    'cancelled' => 'bg-danger',
                                    'rejected' => 'bg-danger',
                                    'pending_approval' => 'bg-pending_approval',
                                ];
                                $badgeClass = $badgeMap[$status] ?? 'bg-secondary';
                            @endphp
                            <tr class="st-table-row">
                                <td class="st-table-cell">{{ $rowNumber }}</td>
                                <td class="st-table-cell">{{ $row->truck_number }}</td>
                                <td class="st-table-cell">{{ $row->mat_doc ?? '-' }}</td>
                                <td class="st-table-cell">{{ $row->vendor_name ?? '-' }}</td>
                                <td class="st-table-cell">{{ $whGateLabel }}</td>
                                <td class="st-table-cell st-td-center">
                                    @php $dir = strtolower($row->direction ?? ''); @endphp
                                    @if($dir === 'inbound')
                                        <span class="st-badge-modern st-badge-modern--inbound">
                                            Inbound
                                        </span>
                                    @elseif($dir === 'outbound')
                                        <span class="st-badge-modern st-badge-modern--outbound">
                                            Outbound
                                        </span>
                                    @else
                                        {{ strtoupper($row->direction ?? '') }}
                                    @endif
                                </td>
                                <td class="st-table-cell">{{ $fmt($row->planned_start ?? null) }}</td>
                                <td class="st-table-cell">{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</td>
                                <td class="st-table-cell">{{ !empty($row->arrival_time) ? $fmt($row->arrival_time) : '-' }}</td>
                                <td class="st-table-cell st-td-center">
                                    @if ($leadTimeMinutes !== null)
                                        @php
                                            $m = (int) $leadTimeMinutes;
                                            $h = $m / 60;
                                        @endphp
                                        <div class="st-leadtime">
                                            {{ $m }} Min
                                            @if ($h >= 1)
                                                <div class="st-leadtime-sub">({{ rtrim(rtrim(number_format($h, 2), '0'), '.') }}h)</div>
                                            @endif
                                            @if ($waitingMinutes !== null || $processMinutes !== null)
                                                <div class="st-leadtime-meta">
                                                    @if ($waitingMinutes !== null)
                                                        W:{{ (int) $waitingMinutes }}m
                                                    @endif
                                                    @if ($processMinutes !== null)
                                                        @if ($waitingMinutes !== null) | @endif
                                                        P:{{ (int) $processMinutes }}m
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="st-table-cell st-td-center">
                                    @if ($status === 'completed' && (empty($row->actual_start) || empty($row->actual_finish)))
                                        <span class="badge bg-status-changes">Data Error</span>
                                    @elseif ($plannedDurationMinutes === null || $plannedDurationMinutes <= 0 || $leadTimeMinutes === null)
                                        -
                                    @elseif ($targetStatus === 'achieve')
                                        <span class="st-table__status-badge st-status-on-time">Achieve</span>
                                    @elseif ($targetStatus === 'not_achieve')
                                        <span class="st-table__status-badge st-status-late">Not Achieve</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="st-table-cell st-td-center">
                                    @if ($lateDisplay === 'late')
                                        <span class="st-table__status-badge st-status-late">Late</span>
                                    @elseif ($lateDisplay === 'on_time')
                                        <span class="st-table__status-badge st-status-on-time">On Time</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="st-table-cell st-td-center">
                                    <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_',' ', $status)) }}</span>
                                </td>
                                <td class="st-table-cell st-td-center">
                                    <span class="st-table__status-badge {{ $blockingClass }}">{{ $blockingLabel }}</span>
                                </td>
                                <td class="st-table-cell st-td-center">
                                    <div class="st-action-dropdown">
                                        <button type="button" class="st-btn st-btn--ghost st-action-trigger st-action-trigger--compact">
                                            &#x22ee;
                                        </button>
                                        <div class="st-action-menu">
                                            @if ($slotTypeVal === 'planned')
                                                @if ($status === 'scheduled')
                                                    @unless(optional(auth()->user())->hasRole('Operator'))
                                                    @can('slots.edit')
                                                    <a href="{{ route('slots.edit', ['slotId' => $row->id]) }}" class="st-action-item">Edit</a>
                                                    @endcan
                                                    @endunless
                                                @endif

                                                @if (!$hasArrival && $status === 'scheduled')
                                                    <a href="{{ route('slots.arrival', ['slotId' => $row->id]) }}" class="st-action-item">Arrival</a>
                                                @elseif (in_array($status, ['waiting'], true))
                                                    <a href="{{ route('slots.start', ['slotId' => $row->id]) }}" class="st-action-item">Start</a>
                                                @endif
                                            @endif

                                            @if ($status === 'in_progress')
                                                <a href="{{ route('slots.complete', ['slotId' => $row->id]) }}" class="st-action-item">Complete</a>
                                            @endif

                                            @if ($status === 'scheduled')
                                                @can('slots.cancel')
                                                <a href="{{ route('slots.cancel', ['slotId' => $row->id]) }}" class="st-action-item st-action-item--danger btn-cancel-slot">Cancel</a>
                                                @endcan
                                            @endif

                                            @if (!empty($row->ticket_number) && in_array($status, ['scheduled', 'waiting', 'in_progress'], true))
                                                @unless(optional(auth()->user())->hasRole('Operator'))
                                                @can('slots.ticket')
                                                <a href="{{ route('slots.ticket', ['slotId' => $row->id]) }}" class="st-action-item" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">Print Ticket</a>
                                                @endcan
                                                @endunless
                                            @endif

                                            <a href="{{ route('slots.show', ['slotId' => $row->id]) }}" class="st-action-item">View</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="st-table-empty st-text-center st-text--muted st-py-16">No Slots Found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script type="application/json" id="slots_index_config">{!! json_encode([
    'suggestUrl' => route('slots.search_suggestions'),
]) !!}</script>
@vite(['resources/js/pages/slots-index.js'])
@endpush

