@extends('layouts.app')

@section('title', 'Slots - Slot Time Management')
@section('page_title', 'Slots')

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

    <section class="st-row st-section-compact">
        <div class="st-col-12 st-flex st-flex-col">
            <div class="st-card tw-card tw-card--table st-card--flush">
                <div class="tw-card__body">
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
        </div>
    </section>

    <section class="st-row st-section-compact st-section-compact--tight">
        <div class="st-col-12 st-flex st-flex-col">
            <div class="st-card tw-card tw-card--table st-card--flush">
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
                <div class="st-table-wrapper st-minh-400 st-p-16">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="st-w-40">#</th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">PO</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="truck" title="Filter">⏷</button>
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="mat_doc" title="Filter">⏷</button>
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="vendor" title="Filter">⏷</button>
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
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse" title="Sort">⇅</button>
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
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" title="Sort">⇅</button>
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="planned_start" data-type="date" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="planned_start" title="Filter">⏷</button>
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="planned_finish" data-type="date" title="Sort">⇅</button>
                                        </span>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Arrival</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="arrival" data-type="date" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="arrival_presence" title="Filter">⏷</button>
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="lead_time" data-type="duration" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="lead_time" title="Filter">⏷</button>
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="target_status" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="target_status" title="Filter">⏷</button>
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
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="late" title="Sort">⇅</button>
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
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="status" title="Sort">⇅</button>
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
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="blocking" title="Sort">⇅</button>
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
                                <td class="st-table-cell">
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
                                <td class="st-table-cell">
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
                                <td class="st-table-cell">
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
                                <td class="st-table-cell">
                                    @if ($lateDisplay === 'late')
                                        <span class="st-table__status-badge st-status-late">Late</span>
                                    @elseif ($lateDisplay === 'on_time')
                                        <span class="st-table__status-badge st-status-on-time">On Time</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="st-table-cell">
                                    <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_',' ', $status)) }}</span>
                                </td>
                                <td class="st-table-cell">
                                    <span class="st-table__status-badge {{ $blockingClass }}">{{ $blockingLabel }}</span>
                                </td>
                                <td class="st-table-cell">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltips handled globally in resources/js/main.js (st-global-tooltip)

    // Initialize jQuery UI datepicker for date inputs
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};
    var dateInputs = document.querySelectorAll('input[type="date"][form="slot-filter-form"]');
    dateInputs.forEach(function(input) {
        if (!window.jQuery || !window.jQuery.fn.datepicker) return;
        if (input.getAttribute('data-st-datepicker') === '1') return;
        input.setAttribute('data-st-datepicker', '1');
        try { input.type = 'text'; } catch (e) {}

        window.jQuery(input).datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShowDay: function(date) {
                var ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                if (holidayData[ds]) {
                    return [true, 'is-holiday', holidayData[ds]];
                }
                return [true, '', ''];
            }
        });
    });

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

    var filterForm = document.getElementById('slot-filter-form');
    if (!filterForm) return;

    var tbody = filterForm.querySelector('tbody');
    var isLoading = false;

    function setLoading(loading) {
        isLoading = loading;
        if (tbody) {
            tbody.style.opacity = loading ? '0.5' : '';
        }
    }

    function buildQueryStringFromForm() {
        var fd = new FormData(filterForm);
        var params = new URLSearchParams();
        fd.forEach(function (value, key) {
            if (value === '' || value === null || typeof value === 'undefined') return;
            params.append(key, value);
        });
        return params.toString();
    }

    function syncFormFromUrl() {
        var params = new URLSearchParams(window.location.search);
        Array.prototype.slice.call(filterForm.elements).forEach(function (el) {
            if (!el || !el.name) return;
            var name = el.name;
            var values = params.getAll(name);
            if (!values || values.length === 0) {
                if (name === 'page_size') {
                    el.value = 'all';
                } else {
                    el.value = '';
                }
                return;
            }
            el.value = values[0];
        });
    }

    // Global variables for confirmation dialog
    let pendingCancelUrl = null;
    let currentSlotNumber = '';

    // Show confirmation dialog
    function showConfirmDialog(slotNumber = '') {
        const dialog = document.getElementById('customConfirmDialog');
        const slotNumberElement = document.getElementById('slotNumber');
        const rejectReason = document.getElementById('rejectReason');
        const form = document.getElementById('cancel-booking-form');

        if (dialog) {
            dialog.style.display = 'flex';
            if (slotNumberElement && slotNumber) {
                currentSlotNumber = slotNumber;
                slotNumberElement.textContent = slotNumber;
            }
            if (form && pendingCancelUrl) {
                form.setAttribute('action', pendingCancelUrl);
            }
            if (rejectReason) {
                rejectReason.value = '';
                rejectReason.focus();
            }
        }
    }

    // Hide confirmation dialog
    function hideConfirmDialog() {
        const dialog = document.getElementById('customConfirmDialog');
        if (dialog) {
            dialog.style.display = 'none';
        }
        pendingCancelUrl = null;
    }

    // Setup event listeners for confirmation dialog
    (function setupCancelBookingDialog() {
        const dialog = document.getElementById('customConfirmDialog');
        const btnNo = document.getElementById('confirmRejectNo');
        const form = document.getElementById('cancel-booking-form');
        const reasonEl = document.getElementById('rejectReason');

        if (btnNo) {
            btnNo.addEventListener('click', function () {
                hideConfirmDialog();
            });
        }

        if (dialog) {
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) {
                    hideConfirmDialog();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                const reason = reasonEl ? reasonEl.value.trim() : '';
                if (!reason) {
                    e.preventDefault();
                    if (reasonEl) {
                        reasonEl.focus();
                    }
                }
            });
        }
    })();

    function bindCancelConfirm() {
        document.querySelectorAll('.btn-cancel-slot').forEach(function (btn) {
            if (btn.getAttribute('data-confirm-bound') === '1') return;
            btn.setAttribute('data-confirm-bound', '1');

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                pendingCancelUrl = this.getAttribute('href');

                // Extract slot number from the row (adjust selector as needed)
                const row = this.closest('tr');
                let slotNumber = 'this booking';
                if (row) {
                    const slotCell = row.querySelector('td:nth-child(2)'); // Adjust index if needed
                    if (slotCell) {
                        slotNumber = slotCell.textContent.trim();
                    }
                }

                showConfirmDialog(slotNumber);
            });
        });
    }

    function ajaxReload(pushState) {
        if (isLoading) return;

        // Update indicators immediately (do not wait for network)
        try { setupActiveFilters(); } catch (e) {}
        try { setupSorting(); } catch (e) {}

        setLoading(true);

        var qs = buildQueryStringFromForm();
        var url = window.location.pathname + (qs ? ('?' + qs) : '');

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newForm = doc.getElementById('slot-filter-form');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tbody && newTbody) {
                    tbody.innerHTML = newTbody.innerHTML;
                }
                bindCancelConfirm();
                syncActionTooltips();
                setupActiveFilters(); // Refresh active filter indicators
                setupSorting(); // Refresh active sort indicators
                if (pushState) {
                    window.history.pushState(null, '', url);
                }
            })
            .catch(function (err) {
                console.error('AJAX reload failed:', err);
                // window.location.href = url; // Disabled to prevent refresh loop
            })
            .finally(function () {
                setLoading(false);
            });
    }

    var openPanel = null;

    function closeOpenPanel() {
        if (openPanel) {
            openPanel.style.display = 'none';
            if (openPanel._stOrigParent) {
                try {
                    if (openPanel._stOrigNext && openPanel._stOrigNext.parentNode === openPanel._stOrigParent) {
                        openPanel._stOrigParent.insertBefore(openPanel, openPanel._stOrigNext);
                    } else {
                        openPanel._stOrigParent.appendChild(openPanel);
                    }
                } catch (e) {
                    // ignore
                }
                openPanel._stOrigParent = null;
                openPanel._stOrigNext = null;
            }
            openPanel = null;
        }
    }

    function openPanelAtTrigger(trigger, panel, panelWidth) {
        var rect = trigger.getBoundingClientRect();
        var viewportWidth = window.innerWidth;
        var viewportHeight = window.innerHeight;
        var width = panelWidth || 280;
        var margin = 8;

        // Portal panel to body to avoid "fixed inside transformed parent" issues
        if (!panel._stOrigParent) {
            panel._stOrigParent = panel.parentNode;
            panel._stOrigNext = panel.nextSibling;
        }
        if (panel.parentNode !== document.body) {
            document.body.appendChild(panel);
        }

        // Make visible to measure actual height
        panel.style.position = 'fixed';
        panel.style.display = 'block';
        panel.style.visibility = 'hidden';
        panel.style.width = width + 'px';

        var measuredHeight = panel.offsetHeight || 300;

        // Horizontal: align left edge with trigger; if overflow right, align right edge to trigger
        var left = rect.left;
        if (left + width > viewportWidth - margin) {
            left = rect.right - width;
        }
        left = Math.max(margin, Math.min(left, viewportWidth - margin - width));

        // Vertical: prefer below; if overflow bottom, flip above
        var top = rect.bottom + 6;
        if (top + measuredHeight > viewportHeight - margin) {
            top = rect.top - 6 - measuredHeight;
        }
        top = Math.max(margin, Math.min(top, viewportHeight - margin - measuredHeight));

        panel.style.visibility = '';
        panel.style.top = Math.round(top) + 'px';
        panel.style.left = Math.round(left) + 'px';
        panel.style.zIndex = '9999';
        panel.style.maxHeight = Math.max(120, (viewportHeight - top - margin)) + 'px';

        openPanel = panel;
    }

    function setupDropdownFilter(filterName, panelWidth) {
        var trigger = document.querySelector('.st-filter-trigger[data-filter="' + filterName + '"]');
        var panel = document.querySelector('.st-filter-panel[data-filter-panel="' + filterName + '"]');
        if (!trigger || !panel) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (openPanel === panel) {
                closeOpenPanel();
            } else {
                closeOpenPanel();
                openPanelAtTrigger(trigger, panel, panelWidth);
            }
        });

        var clearBtn = panel.querySelector('.st-filter-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();

                // Clear all selects in panel
                var selects = panel.querySelectorAll('select');
                Array.prototype.slice.call(selects).forEach(function (s) {
                    if (!s) return;
                    try { s.value = ''; } catch (e) {}
                });

                // Clear all inputs in panel (including hidden ones)
                var inputs = panel.querySelectorAll('input');
                Array.prototype.slice.call(inputs).forEach(function (i) {
                    if (!i) return;
                    try { i.value = ''; } catch (e) {}
                });

                ajaxReload(true);
            });
        }

        var selects = panel.querySelectorAll('select');
        Array.prototype.slice.call(selects).forEach(function (s) {
            if (!s) return;
            s.addEventListener('change', function () {
                ajaxReload(true);
            });
        });

        var inputs = panel.querySelectorAll('input');
        Array.prototype.slice.call(inputs).forEach(function (i) {
            if (!i) return;
            if (i.type === 'hidden') return;
            i.addEventListener('change', function () {
                ajaxReload(true);
            });
            i.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    ajaxReload(true);
                }
            });
            i.addEventListener('change', function () {
                ajaxReload(true);
            });
    }

    function setupActiveFilters() {
        // Clear all active filter indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-filter', 'st-filter-header--active-filter');
        });
        document.querySelectorAll('.st-filter-trigger').forEach(function(btn) {
            btn.classList.remove('st-filter-trigger--active');
            btn.classList.remove('is-filtered');
        });

        // Check each filter field for active values
        var activeFilters = [];

        // Text input filters
        var textFilters = ['truck', 'mat_doc', 'vendor', 'po_number'];
        textFilters.forEach(function(filterName) {
            var input = filterForm.querySelector('input[name="' + filterName + '"]');
            if (input && input.value && input.value.trim() !== '') {
                activeFilters.push(filterName);
            }
        });

        // Select filters (multiple and single)
        var selectFilters = ['warehouse_id[]', 'gate[]', 'direction[]', 'late[]', 'status[]', 'blocking[]', 'target_status[]'];
        selectFilters.forEach(function(filterName) {
            var selects = filterForm.querySelectorAll('select[name="' + filterName + '"]');
            selects.forEach(function(select) {
                if (select.value && select.value !== '') {
                    // Map filter names to trigger data-filter attributes
                    var triggerName = filterName.replace('[]', '').replace('_id', '');
                    if (triggerName === 'warehouse') triggerName = 'whgate';
                    if (triggerName === 'gate') triggerName = 'whgate';
                    if (!activeFilters.includes(triggerName)) {
                        activeFilters.push(triggerName);
                    }
                }
            });
        });

        // Date range filters
        var dateFilters = ['date_from', 'date_to', 'arrival_from', 'arrival_to'];
        dateFilters.forEach(function(filterName) {
            var input = filterForm.querySelector('input[name="' + filterName + '"]');
            if (input && input.value && input.value.trim() !== '') {
                var triggerName = filterName.includes('arrival') ? 'arrival_presence' : 'planned_start';
                if (!activeFilters.includes(triggerName)) {
                    activeFilters.push(triggerName);
                }
            }
        });

        // Number range filters
        var numberFilters = ['lead_time_min', 'lead_time_max'];
        numberFilters.forEach(function(filterName) {
            var input = filterForm.querySelector('input[name="' + filterName + '"]');
            if (input && input.value && input.value.trim() !== '') {
                if (!activeFilters.includes('lead_time')) {
                    activeFilters.push('lead_time');
                }
            }
        });

        // Apply active indicators
        activeFilters.forEach(function(filterName) {
            var activeFilterBtn = document.querySelector('.st-filter-trigger[data-filter="' + filterName + '"]');
            if (activeFilterBtn) {
                activeFilterBtn.classList.add('st-filter-trigger--active');
                activeFilterBtn.classList.add('is-filtered');
            }
        });
    }

    function setupSorting() {
        var sortInput = filterForm.querySelector('input[name="sort"]');
        var dirInput = filterForm.querySelector('input[name="dir"]');
        if (!sortInput || !dirInput) return;

        var currentSort = String(sortInput.value || '');
        var currentDir = String(dirInput.value || 'desc');

        // Clear all active indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-sort', 'st-filter-header--active-sort');
        });
        document.querySelectorAll('.st-sort-trigger').forEach(function(btn) {
            btn.classList.remove('st-sort-trigger--active');
        });

        // Set active indicator for current sort
        if (currentSort) {
            var activeSortBtn = document.querySelector('.st-sort-trigger[data-sort="' + currentSort + '"]');
            if (activeSortBtn) {
                activeSortBtn.classList.add('st-sort-trigger--active');
            }
        }

        document.querySelectorAll('.st-sort-trigger').forEach(function (btn) {
            if (!btn || btn.getAttribute('data-sort-bound') === '1') return;
            btn.setAttribute('data-sort-bound', '1');

            var key = String(btn.getAttribute('data-sort') || '');
            if (!key) return;

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var nowSort = String(sortInput.value || '');
                var nowDir = String(dirInput.value || 'desc');
                var nextDir = 'asc';
                var nextSort = key;

                // 3-step toggle: asc -> desc -> off
                if (nowSort === key) {
                    if (nowDir === 'asc') {
                        nextDir = 'desc';
                    } else {
                        nextSort = '';
                        nextDir = '';
                    }
                } else {
                    nextDir = (key === 'lead_time') ? 'desc' : 'asc';
                }

                sortInput.value = nextSort;
                dirInput.value = nextDir;
                ajaxReload(true);
            });
        });
    }

    // Ensure form is synced with URL parameters on load (clears browser-restored values for hidden inputs)
    syncFormFromUrl();

    setupDropdownFilter('status', 260);
    setupDropdownFilter('direction', 220);
    setupDropdownFilter('late', 220);
    setupDropdownFilter('blocking', 240);
    setupDropdownFilter('whgate', 320);
    setupDropdownFilter('planned_start', 320);
    setupDropdownFilter('truck', 260);
    setupDropdownFilter('mat_doc', 260);
    setupDropdownFilter('vendor', 260);
    setupDropdownFilter('arrival_presence', 280);
    setupDropdownFilter('lead_time', 280);
    setupDropdownFilter('target_status', 240);
    setupActiveFilters(); // Initialize active filter indicators
    setupSorting();

    // Filter gates based on warehouse selection
    function filterGateOptions() {
        var warehouseSelects = document.querySelectorAll('select[name="warehouse_id[]"]');
        var gateSelect = document.querySelector('select[name="gate[]"]');
        if (!warehouseSelects || !gateSelect) return;

        // Get selected warehouse IDs
        var selectedWarehouses = [];
        warehouseSelects.forEach(function(select) {
            if (select.value) {
                selectedWarehouses.push(select.value);
            }
        });

        // Filter gate options
        var options = gateSelect.querySelectorAll('option[data-warehouse-id]');
        options.forEach(function(option) {
            var warehouseId = option.getAttribute('data-warehouse-id');
            if (selectedWarehouses.length === 0 || selectedWarehouses.includes(warehouseId)) {
                option.hidden = false;
            } else {
                option.hidden = true;
                if (option.selected) {
                    option.selected = false;
                }
            }
        });
    }

    // Bind warehouse filter change event
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'warehouse_id[]') {
            filterGateOptions();
        }
    });

    // Initial filter
    filterGateOptions();

    document.addEventListener('click', function (e) {
        var isTrigger = e.target && e.target.closest ? e.target.closest('.st-filter-trigger') : null;
        var isPanel = e.target && e.target.closest ? e.target.closest('.st-filter-panel') : null;
        if (!isTrigger && !isPanel) {
            closeOpenPanel();
        }
    });

    bindCancelConfirm();

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        if (t.matches('select[form="slot-filter-form"], input[type="date"][form="slot-filter-form"]')) {
            ajaxReload(true);
            return;
        }
        if (t.closest && t.closest('#slot-filter-form') && t.matches('select')) {
            ajaxReload(true);
            return;
        }
    });

    window.addEventListener('popstate', function () {
        syncFormFromUrl();
        ajaxReload(false);
    });

    // Global search suggestions (no auto-submit)
    var searchInput = document.querySelector('input[name="q"][form="slot-filter-form"]');
    var suggestionBox = document.getElementById('slot-search-suggestions');
    var suggestUrl = "{{ route('slots.search_suggestions') }}";
    if (searchInput && suggestionBox) {
        function hideSuggestions() {
            suggestionBox.style.display = 'none';
            suggestionBox.innerHTML = '';
        }

        window.selectSuggestion = function (text) {
            searchInput.value = text;
            hideSuggestions();
            ajaxReload(true);
        };

        searchInput.addEventListener('keyup', function (e) {
            var value = (searchInput.value || '').trim();

            if (e.key === 'Enter') {
                hideSuggestions();
                ajaxReload(true);
                return;
            }

            if (value.length === 0) {
                hideSuggestions();
                return;
            }

            fetch(suggestUrl + '?q=' + encodeURIComponent(value), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.length > 0) {
                        var html = '';
                        data.forEach(function (item) {
                            var t = (item && item.text) ? item.text : '';
                            var h = (item && item.highlighted) ? item.highlighted : t;
                            html += '<div class="st-suggestion-item" onclick="selectSuggestion(\'' + String(t).replace(/'/g, "\\'") + '\')">' + h + '</div>';
                        });
                        suggestionBox.innerHTML = html;
                        suggestionBox.style.display = 'block';
                    } else {
                        hideSuggestions();
                    }
                })
                .catch(function () {
                    hideSuggestions();
                });
        });

        document.addEventListener('click', function (e) {
            var inBox = e.target && e.target.closest ? e.target.closest('#slot-search-suggestions') : null;
            var inInput = e.target === searchInput;
            if (!inBox && !inInput) {
                hideSuggestions();
            }
        });
    }
});
</script>
@endpush
