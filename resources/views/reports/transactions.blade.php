@extends('layouts.app')

@section('title', 'Transactions - Slot Time Management')
@section('page_title', 'Transactions')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-form-row st-items-end">
                <div class="st-form-field st-flex-1 st-minw-260 st-relative">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        name="q"
                        form="transactions-filter-form"
                        class="st-input"
                        placeholder="PO, Ticket, MAT DOC, Vendor, Etc"
                        value="{{ $q ?? '' }}"
                    >
                    <div id="transaction-search-suggestions" class="st-suggestions st-suggestions--transactions st-hidden"></div>
                </div>
                <div class="st-form-field st-maxw-250 st-relative">
                    <label class="st-label">Date Range</label>
                    <div id="transaction_reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
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
                            <option value="{{ $ps }}" {{ ($pageSize ?? 'all') === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-flex-0">
                    <a id="transactions-excel-link" href="{{ route('reports.transactions', array_merge(request()->query(), ['page_size' => 'all', 'export' => 'excel'])) }}" class="st-btn st-btn--primary">Excel</a>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1">
                <form method="GET" id="transactions-filter-form" data-multi-sort="1" action="{{ route('reports.transactions') }}">
                    @php
                        $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                        $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                    @endphp
                    @foreach ($sortsArr as $i => $s)
                        @php $d = $dirsArr[$i] ?? 'desc'; @endphp
                        <input type="hidden" name="sort[]" value="{{ $s }}">
                        <input type="hidden" name="dir[]" value="{{ $d }}">
                    @endforeach
                    <div class="st-table-wrapper st-table-wrapper--minh-400">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th class="st-table-col-40">#</th>
                                    <th class="st-table-col-90">
                                        <div class="st-filter-header st-filter-header--inline-compact">
                                            <span>Type</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="type" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger st-filter-trigger--pill" data-filter="type" title="Filter Type">
                                                <span class="st-text--xs-9 st-leading-1">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-filter-panel="type">
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
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="type">
                                                <div class="st-font-semibold st-mb-6">Sort Type</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="type" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="type" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">PO</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="po" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="po">
                                                <div class="st-font-semibold st-mb-6">PO Filter</div>
                                                <input type="text" name="po" class="st-input" placeholder="Search PO..." value="{{ $po ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="po">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="po">
                                                <div class="st-font-semibold st-mb-6">Sort PO</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="po" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="po" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Ticket</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="ticket" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="ticket" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="ticket">
                                                <div class="st-font-semibold st-mb-6">Ticket Filter</div>
                                                <input type="text" name="ticket" class="st-input" placeholder="Search Ticket..." value="{{ $ticket ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="ticket">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="ticket">
                                                <div class="st-font-semibold st-mb-6">Sort Ticket</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="ticket" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="ticket" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">MAT DOC</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="mat_doc" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="mat_doc">
                                                <div class="st-font-semibold st-mb-6">MAT DOC Filter</div>
                                                <input type="text" name="mat_doc" class="st-input" placeholder="Search MAT DOC..." value="{{ $mat_doc ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="mat_doc">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="mat_doc">
                                                <div class="st-font-semibold st-mb-6">Sort MAT DOC</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="mat_doc" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="mat_doc" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header st-filter-header--inline-compact">
                                            <span>Vendor</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger st-filter-trigger--pill" data-filter="vendor" title="Filter Vendor">
                                                <span class="st-text--xs-9 st-leading-1">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="vendor">
                                                <div class="st-font-semibold st-mb-6">Vendor Filter</div>
                                                <input type="text" name="vendor" class="st-input" placeholder="Search Vendor..." value="{{ $vendor ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="vendor">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="vendor">
                                                <div class="st-font-semibold st-mb-6">Sort Vendor</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="vendor" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="vendor" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header st-filter-header--inline-compact">
                                            <span>Warehouse</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger st-filter-trigger--pill" data-filter="warehouse" title="Filter Warehouse">
                                                <span class="st-text--xs-9 st-leading-1">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-260" data-filter-panel="warehouse">
                                                <div class="st-font-semibold st-mb-6">Warehouse Filter</div>
                                                <select name="warehouse_id[]" class="st-select st-filter-warehouse-select st-select--panel">
                                                    <option value="">(All)</option>
                                                    @foreach ($warehouses as $wh)
                                                        <option value="{{ $wh->id }}" {{ in_array((string) $wh->id, array_map('strval', $warehouseFilter ?? []), true) ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="warehouse">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="warehouse">
                                                <div class="st-font-semibold st-mb-6">Sort Warehouse</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="warehouse" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="warehouse" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header st-filter-header--inline-compact">
                                            <span>Direction</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger st-filter-trigger--pill" data-filter="direction" title="Filter Direction">
                                                <span class="st-text--xs-9 st-leading-1">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-filter-panel="direction">
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
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="direction">
                                                <div class="st-font-semibold st-mb-6">Sort Direction</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="direction" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="direction" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Arrival</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="arrival" data-type="date" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="arrival_presence" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-260 st-maxh-220" data-filter-panel="arrival_presence">
                                                <div class="st-font-semibold st-mb-6">Arrival Date</div>
                                                <input type="text" name="arrival_date_range" id="arrival_date_range" class="st-input st-input--cursor" placeholder="Select Arrival Date Range" readonly>
                                                <input type="hidden" name="arrival_date_from" id="arrival_date_from" value="{{ $arrival_date_from ?? '' }}">
                                                <input type="hidden" name="arrival_date_to" id="arrival_date_to" value="{{ $arrival_date_to ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="arrival_presence">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="arrival">
                                                <div class="st-font-semibold st-mb-6">Sort Arrival</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="arrival" data-dir="desc">
                                                    Newest
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="arrival" data-dir="asc">
                                                    Oldest
                                                </button>
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
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-260" data-filter-panel="lead_time">
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
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="lead_time">
                                                <div class="st-font-semibold st-mb-6">Sort Lead Time</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="lead_time" data-dir="asc">
                                                    Fastest
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="lead_time" data-dir="desc">
                                                    Slowest
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header st-filter-header--inline-compact">
                                            <span>Target Status</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="target_status" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger st-filter-trigger--pill" data-filter="target_status" title="Filter Target Status">
                                                <span class="st-text--xs-9 st-leading-1">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-filter-panel="target_status">
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
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="target_status">
                                                <div class="st-font-semibold st-mb-6">Sort Target Status</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="target_status" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="target_status" data-dir="desc">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header st-filter-header--inline-compact">
                                            <span>ARRIVAL STATUS</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="late" data-type="number" title="Sort">â‡…</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger st-filter-trigger--pill" data-filter="late" title="Filter Late">
                                                <span class="st-text--xs-9 st-leading-1">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200 st-maxh-220" data-filter-panel="late">
                                                <div class="st-font-semibold st-mb-6">Late Filter</div>
                                                <select name="late[]" class="st-select st-filter-late-select st-select--panel">
                                                    <option value="">(All)</option>
                                                    <option value="on_time" {{ in_array('on_time', $lateFilter ?? [], true) ? 'selected' : '' }}>On Time</option>
                                                    <option value="late" {{ in_array('late', $lateFilter ?? [], true) ? 'selected' : '' }}>Late</option>
                                                </select>
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="late">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="late">
                                                <div class="st-font-semibold st-mb-6">Sort Late</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="late" data-dir="asc">
                                                    Terkecil
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="late" data-dir="desc">
                                                    Terbesar
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">User</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="user" data-type="text" title="Sort">â‡…</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="user" title="Filter">â·</button>
                                            </span>
                                            <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240 st-maxh-220" data-filter-panel="user">
                                                <div class="st-font-semibold st-mb-6">User Filter</div>
                                                <input type="text" name="user" class="st-input" placeholder="Search User..." value="{{ $user ?? '' }}">
                                                <div class="st-panel__actions">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="user">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-200" data-sort-panel="user">
                                                <div class="st-font-semibold st-mb-6">Sort User</div>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="user" data-dir="asc">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option st-sort-option--compact" data-sort="user" data-dir="desc">
                                                    Z-A
                                                </button>
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
                                                return \Carbon\Carbon::parse((string) $v)->format('d M Y H:i');
                                            } catch (\Throwable $e) {
                                                return (string) $v;
                                            }
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            @if ($slotTypeVal === 'unplanned')
                                                <span class="badge bg-unplanned">Unplanned</span>
                                            @else
                                                <span class="badge bg-planned">Planned</span>
                                            @endif
                                        </td>
                                        <td>{{ $r->truck_number ?? '' }}</td>
                                        <td>{{ $r->ticket_number ?? '' }}</td>
                                        <td>{{ $r->mat_doc ?? '' }}</td>
                                        <td>{{ $r->vendor_name ?? '-' }}</td>
                                        <td>{{ $r->warehouse_name ?? '-' }}</td>
                                        <td>
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
                                        <td>
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
                                        <td>
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
                                        <td>
                                            @if ($lateDisplay === 'late')
                                                <span class="st-table__status-badge st-status-late">Late</span>
                                            @elseif ($lateDisplay === 'on_time')
                                                <span class="st-table__status-badge st-status-on-time">On Time</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $r->created_by_name ?? $r->created_by_email ?? '-' }}</td>
                                        <td>
                                            <div class="tw-actionbar">
                                                <a href="{{ route('slots.show', ['slotId' => $r->id]) }}" class="tw-action" data-tooltip="View" aria-label="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="st-text-center st-text--muted st-table-empty--roomy">No Transactions Found</td>
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

