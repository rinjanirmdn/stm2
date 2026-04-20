@extends('layouts.app')

@section('title', 'Transactions - e-Docking Control System')
@section('page_title', 'Reports')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-form-row st-items-end" style="flex-wrap: wrap; gap: 12px;">
                <div class="st-form-field st-flex-1 st-minw-260 st-relative" style="min-width: 200px;">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        name="q"
                        form="transactions-filter-form"
                        class="st-input"
                        placeholder="PO, Ticket, SJ, Vendor, Etc"
                        value="{{ $q ?? '' }}"
                    >
                    <div id="transaction-search-suggestions" class="st-suggestions st-suggestions--transactions st-hidden"></div>
                </div>
                <div class="st-form-field st-maxw-250 st-relative">
                    <label class="st-label">Date Range</label>
                    <div id="transaction_reportrange" class="st-dashboard-range-picker" data-auto-submit="false">
                        <span>Select range</span>
                    </div>
                    <input type="hidden" name="date_from" id="date_from" form="transactions-filter-form" value="{{ $date_from ?? '' }}">
                    <input type="hidden" name="date_to" id="date_to" form="transactions-filter-form" value="{{ $date_to ?? '' }}">
                </div>
                <div class="st-form-field st-flex-0">
                    <button type="button" id="clear-date-range" class="st-btn st-btn--outline-primary st-btn--pad-md" title="Reset all filters">
                        Reset
                    </button>
                </div>
                <div class="st-form-field st-maxw-120">
                    <label class="st-label">Show</label>
                    <select name="page_size" form="transactions-filter-form" class="st-select">
                        @foreach ($pageSizeAllowed as $ps)
                            <option value="{{ $ps }}" {{ ($pageSize ?? '10') === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-flex-0 st-flex st-align-center" style="gap: 8px;">
                    <button type="button" id="transactions-excel-link" class="st-btn st-btn--primary" data-export-url="{{ route('reports.transactions', array_merge(request()->query(), ['page_size' => 'all', 'export' => 'excel'])) }}">Export</button>

                    @hasanyrole('Super Account|Section Head|super account|section head')
                        <button type="button" class="st-btn st-btn--primary" id="btn-import-offline">Import</button>
                    @endhasanyrole
                </div>
            </div>
        </div>
    </div>

    {{-- Import Offline Modal --}}
    @hasanyrole('Super Account|Section Head|super account|section head')
    <div id="modal-import-offline" class="st-modal">
        <div class="st-modal__content st-maxw-500">
            <div class="st-modal__header">
                <h3 class="st-modal__title">Import Offline Data</h3>
                <button type="button" class="st-btn st-btn--sm st-modal__close" id="modal-import-close">&times;</button>
            </div>
            <div class="st-modal__body">
                <p class="st-text--muted st-mb-4">Use this feature to import transactions manually recorded during server or network outages.</p>
                <button type="button" id="btn-download-template" class="st-link st-font-semibold st-mb-8 st-block" style="background:none;border:none;cursor:pointer;padding:0;text-align:left;" data-export-url="{{ route('reports.offline_import.template') }}" data-filename="offline_import_template.xlsx"><i class="fa-solid fa-download st-mr-2"></i> Download Template</button>
                
                <form id="form-import-offline" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="st-form-field">
                        <label class="st-label">Upload Excel File</label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="st-input" required>
                    </div>
                    <div id="import-offline-alert" class="st-alert st-hidden st-mt-4"></div>
                    <div class="st-form-actions st-mt-4">
                        <button type="submit" class="st-btn st-btn--primary" id="btn-import-submit">Upload & Import</button>
                        <button type="button" class="st-btn st-btn--outline-primary st-modal__close" id="btn-import-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endhasanyrole

    <section class="st-row st-flex-1 st-minh-0">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col st-minh-0">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1 st-minh-0">
                <form method="GET" id="transactions-filter-form" data-multi-sort="1" action="{{ route('reports.transactions') }}" class="st-flex st-flex-col st-flex-1 st-minh-0">
                    @php
                        $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                        $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                    @endphp
                    @foreach ($sortsArr as $i => $s)
                        @php $d = $dirsArr[$i] ?? 'desc'; @endphp
                        <input type="hidden" name="sort[]" value="{{ $s }}">
                        <input type="hidden" name="dir[]" value="{{ $d }}">
                    @endforeach
                    <div class="st-table-wrapper st-table-wrapper--minh-400 st-flex-1 st-maxh-none st-minh-0">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th class="st-table-col-40">#</th>
                                    <th class="st-table-col-90">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Type</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="type" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="type" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-st-position="fixed" data-filter-panel="type">
                                                <div class="st-font-semibold st-mb-6">Type Filter</div>
                                                <select name="slot_type[]" class="st-select st-filter-type-select st-select--panel">
                                                    <option value="">(All)</option>
                                                    <option value="planned" {{ in_array('planned', $slotTypeFilter ?? [], true) ? 'selected' : '' }}>Planned</option>
                                                    <option value="unplanned" {{ in_array('unplanned', $slotTypeFilter ?? [], true) ? 'selected' : '' }}>Unplanned</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="type">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">PO</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="po" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-st-position="fixed" data-filter-panel="po">
                                                <div class="st-font-semibold st-mb-6">PO Filter</div>
                                                <input type="text" name="po" class="st-input" placeholder="Search PO..." value="{{ $po ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="po">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Ticket</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="ticket" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="ticket" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-st-position="fixed" data-filter-panel="ticket">
                                                <div class="st-font-semibold st-mb-6">Ticket Filter</div>
                                                <input type="text" name="ticket" class="st-input" placeholder="Search Ticket..." value="{{ $ticket ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="ticket">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">SJ</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="mat_doc" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-st-position="fixed" data-filter-panel="mat_doc">
                                                <div class="st-font-semibold st-mb-6">SJ Filter</div>
                                                <input type="text" name="mat_doc" class="st-input" placeholder="Search SJ..." value="{{ $mat_doc ?? '' }}">
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
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="vendor" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-st-position="fixed" data-filter-panel="vendor">
                                                <div class="st-font-semibold st-mb-6">Vendor Filter</div>
                                                <input type="text" name="vendor" class="st-input" placeholder="Search Vendor..." value="{{ $vendor ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="vendor">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Warehouse / Gate</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="warehouse" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-260" data-st-position="fixed" data-filter-panel="warehouse">
                                                <div class="st-font-semibold st-mb-6">Warehouse / Gate Filter</div>
                                                <div class="st-mb-8">
                                                    <div class="st-text--xs st-mb-2">Wh</div>
                                                    <select name="warehouse_id[]" class="st-select st-filter-warehouse-select st-select--panel">
                                                        <option value="">(All Wh)</option>
                                                        @foreach ($warehouses as $wh)
                                                            <option value="{{ $wh->id }}" {{ in_array((string) $wh->id, array_map('strval', $warehouseFilter ?? []), true) ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="st-mb-8">
                                                    <div class="st-text--xs st-mb-2">Gate</div>
                                                    <select name="gate_number[]" class="st-select st-select--panel">
                                                        <option value="">(All Gates)</option>
                                                        @foreach ($gates as $gn)
                                                            <option value="{{ $gn }}" {{ in_array((string) $gn, array_map('strval', $gateFilter ?? []), true) ? 'selected' : '' }}>Gate {{ $gn }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="warehouse">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Direction</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="direction" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-st-position="fixed" data-filter-panel="direction">
                                                <div class="st-font-semibold st-mb-6">Direction Filter</div>
                                                <select name="direction[]" class="st-select st-filter-direction-select st-select--panel">
                                                    <option value="">(All)</option>
                                                    <option value="inbound" {{ in_array('inbound', $directionFilter ?? [], true) ? 'selected' : '' }}>Inbound</option>
                                                    <option value="outbound" {{ in_array('outbound', $directionFilter ?? [], true) ? 'selected' : '' }}>Outbound</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="direction">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Arrival</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="arrival" data-type="date" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="arrival_presence" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-260 st-maxh-220" data-st-position="fixed" data-filter-panel="arrival_presence">
                                                <div class="st-font-semibold st-mb-6">Arrival Date Filter</div>
                                                <div id="arrival_reportrange" class="st-dashboard-range-picker" data-auto-submit="false">
                                                    <span>Select range</span>
                                                </div>
                                                <input type="hidden" name="arrival_date_from" id="arrival_from" value="{{ $arrival_date_from ?? '' }}">
                                                <input type="hidden" name="arrival_date_to" id="arrival_to" value="{{ $arrival_date_to ?? '' }}">
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
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-260" data-st-position="fixed" data-filter-panel="lead_time">
                                                <div class="st-font-semibold st-mb-6">Lead Time (min)</div>
                                                <div class="st-flex st-flex-col st-gap-8">
                                                    <div>
                                                        <div class="st-text--xs-11 st-font-semibold st-mb-4">Min</div>
                                                        <input type="number" name="lead_time_min" class="st-input" placeholder="0" value="{{ $lead_time_min ?? '' }}">
                                                    </div>
                                                    <div>
                                                        <div class="st-text--xs-11 st-font-semibold st-mb-4">Max</div>
                                                        <input type="number" name="lead_time_max" class="st-input" placeholder="999" value="{{ $lead_time_max ?? '' }}">
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
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="target_status" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="target_status" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-st-position="fixed" data-filter-panel="target_status">
                                                <div class="st-font-semibold st-mb-6">Target Status Filter</div>
                                                <select name="target_status[]" class="st-select st-filter-target-status-select st-select--panel">
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
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Arrival Status</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="late" data-type="number" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="late" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-filter-panel--wide st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-st-position="fixed" data-filter-panel="late">
                                                <div class="st-font-semibold st-mb-6">Arrival Status Filter</div>
                                                <select name="late[]" class="st-select st-filter-late-select st-select--panel">
                                                    <option value="">(All)</option>
                                                    <option value="on_time" {{ in_array('on_time', $lateFilter ?? [], true) ? 'selected' : '' }}>On Time</option>
                                                    <option value="late" {{ in_array('late', $lateFilter ?? [], true) ? 'selected' : '' }}>Late</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="late">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">User</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="user" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="user" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-st-position="fixed" data-filter-panel="user">
                                                <div class="st-font-semibold st-mb-6">User Filter</div>
                                                <input type="text" name="user" class="st-input" placeholder="Search User..." value="{{ $user ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="user">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $i => $r)
                                    @php
                                        $slotTypeVal = (string) ($r->slot_type ?? 'planned');

                                        $leadTimeMinutes = null;
                                        $waitingMinutes = null;
                                        $processMinutes = null;
                                        try {
                                            $arrival = !empty($r->arrival_time) ? (string) $r->arrival_time : null;
                                            $start = !empty($r->actual_start) ? (string) $r->actual_start : null;
                                            $finish = !empty($r->actual_finish) ? (string) $r->actual_finish : null;

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

                                            if ($processMinutes !== null) {
                                                $leadTimeMinutes = (int) $processMinutes + (int) max(0, (int) ($waitingMinutes ?? 0));
                                            }
                                        } catch (\Throwable $e) {
                                            $leadTimeMinutes = null;
                                            $waitingMinutes = null;
                                            $processMinutes = null;
                                        }

                                        $targetDurationMinutes = isset($r->target_duration_minutes) ? (int) $r->target_duration_minutes : null;
                                        $targetStatus = null;
                                        if ($targetDurationMinutes !== null && $leadTimeMinutes !== null) {
                                            $threshold = $targetDurationMinutes + 15;
                                            $targetStatus = $leadTimeMinutes <= $threshold ? 'achieve' : 'not_achieve';
                                        }

                                        $lateDisplay = null;
                                        if (!empty($r->arrival_time) && $slotTypeVal === 'planned') {
                                            try {
                                                $p = new \DateTime((string) $r->planned_start);
                                                $p->modify('+15 minutes');
                                                $a = new \DateTime((string) $r->arrival_time);
                                                $lateDisplay = $a > $p ? 'late' : 'on_time';
                                            } catch (\Throwable $e) {
                                                $lateDisplay = null;
                                            }
                                        } else {
                                            $lateDisplay = !empty($r->is_late) ? 'late' : 'on_time';
                                        }

                                        $fmt = function ($v) {
                                            if (empty($v)) return '-';
                                            try {
                                                return \Carbon\Carbon::parse((string) $v)->format('d-m-Y H:i');
                                            } catch (\Throwable $e) {
                                                return (string) $v;
                                            }
                                        };
                                    @endphp
                                    <tr class="st-table-row" style="cursor: pointer;" onclick="if (!event.target.closest('a') && !event.target.closest('button') && !event.target.closest('.st-action-dropdown') && !event.target.closest('input')) { window.location.href = '{{ route('slots.show', ['slotId' => $r->id]) }}'; }">
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            @if ($slotTypeVal === 'unplanned')
                                                <span class="badge bg-unplanned">Unplanned</span>
                                            @else
                                                <span class="badge bg-planned">Planned</span>
                                            @endif
                                        </td>
                                        <td>{{ $r->po_number ?? '' }}</td>
                                        <td>{{ $r->ticket_number ?? '' }}</td>
                                        <td>{{ $r->mat_doc ?? '' }}</td>
                                        <td>{{ $r->vendor_name ?? '-' }}{{ !empty($r->destination) ? ' (' . $r->destination . ')' : '' }}</td>
                                        <td class="st-td-center st-nowrap">
                                            <div class="st-flex st-flex-col st-align-center">
                                                <div class="st-font-semibold">{{ $r->warehouse_name }}</div>
                                                <div class="st-text--xs st-text--muted">Gate {{ $r->gate_number }}</div>
                                            </div>
                                        </td>
                                        <td class="st-td-center">
                                            @php $dir = strtolower($r->direction ?? ''); @endphp
                                            @if($dir === 'inbound')
                                                <span class="st-badge-modern st-badge-modern--inbound">
                                                    Inbound
                                                </span>
                                            @elseif($dir === 'outbound')
                                                <span class="st-badge-modern st-badge-modern--outbound">
                                                    Outbound
                                                </span>
                                            @else
                                                {{ strtoupper((string) ($r->direction ?? '')) }}
                                            @endif
                                        </td>
                                        <td>{{ !empty($r->arrival_time) ? $fmt($r->arrival_time) : '-' }}</td>
                                        <td class="st-td-center">
                                            @if ($leadTimeMinutes !== null)
                                                @php
                                                    $ltMinutes = (int) $leadTimeMinutes;
                                                    $ltHours = $ltMinutes / 60;
                                                @endphp
                                                {{ $ltMinutes }} Min
                                                @if ($ltHours >= 1)
                                                    ({{ rtrim(rtrim(number_format($ltHours, 2), '0'), '.') }} Hours)
                                                @endif
                                                <div class="st-text--xs-10 st-text--muted st-mt-1 st-leading-13">
                                                    @if ($waitingMinutes !== null)
                                                        <div>Waiting: {{ (int) $waitingMinutes }} Min</div>
                                                    @endif
                                                    @if ($processMinutes !== null)
                                                        <div>Process: {{ (int) $processMinutes }} Min</div>
                                                    @endif
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="st-td-center">
                                            @if ($targetDurationMinutes === null || $leadTimeMinutes === null)
                                                -
                                            @elseif ($targetStatus === 'achieve')
                                                <span class="st-table__status-badge st-status-on-time">Achieve</span>
                                            @elseif ($targetStatus === 'not_achieve')
                                                <span class="st-table__status-badge st-status-late">Not Achieve</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="st-td-center">
                                            @if ($lateDisplay === 'late')
                                                <span class="st-table__status-badge st-status-late">Late</span>
                                            @elseif ($lateDisplay === 'on_time')
                                                <span class="st-table__status-badge st-status-on-time">On Time</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="st-td-center">{{ $r->created_by_name ?? $r->created_by_email ?? '-' }}</td>
                                        <td class="st-td-center">
                                            <div class="tw-actionbar">
                                                <a href="{{ route('slots.show', ['slotId' => $r->id]) }}" class="tw-action" data-tooltip="View" aria-label="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="st-table-empty st-text-center st-text--muted st-table-empty--roomy">No Transactions Found</td>
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
<script type="application/json" id="reports_transactions_config">{!! json_encode([
    'suggestUrl' => route('reports.search_suggestions'),
    'baseUrl' => route('reports.transactions'),
]) !!}</script>
@vite(['resources/js/pages/reports-transactions.js'])
@endpush

