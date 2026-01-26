@extends('layouts.app')

@section('title', 'Transactions - Slot Time Management')
@section('page_title', 'Transactions')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="padding:12px;">
            <div class="st-form-row" style="align-items:flex-end;">
                <div class="st-form-field" style="flex:1;min-width:260px;position:relative;">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        name="q"
                        form="transactions-filter-form"
                        class="st-input"
                        placeholder="PO, Ticket, MAT DOC, Vendor, Etc"
                        value="{{ $q ?? '' }}"
                    >
                    <div id="transaction-search-suggestions" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:30;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;max-height:220px;overflow:auto;min-width:260px;"></div>
                </div>
                <div class="st-form-field" style="max-width:250px;position:relative;">
                    <label class="st-label">Date Range</label>
                    <div style="display:flex;gap:6px;">
                        <input
                            type="text"
                            id="date_range"
                            class="st-input"
                            placeholder="Select Date Range"
                            readonly
                            style="cursor:pointer;flex:1;"
                        >
                        <button type="button" id="clear-date-range" class="st-btn st-btn--secondary st-btn--sm" title="Reset all filters" style="padding:6px 12px;">
                            Reset
                        </button>
                    </div>
                    <input type="hidden" name="date_from" id="date_from" form="transactions-filter-form" value="{{ $date_from ?? '' }}">
                    <input type="hidden" name="date_to" id="date_to" form="transactions-filter-form" value="{{ $date_to ?? '' }}">
                </div>
                <div class="st-form-field" style="max-width:120px;">
                    <label class="st-label">Show</label>
                    <select name="page_size" form="transactions-filter-form" class="st-select">
                        @foreach ($pageSizeAllowed as $ps)
                            <option value="{{ $ps }}" {{ ($pageSize ?? 'all') === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="flex:0 0 auto;">
                    <a id="transactions-excel-link" href="{{ route('reports.transactions', array_merge(request()->query(), ['page_size' => 'all', 'export' => 'excel'])) }}" class="st-btn st-btn--primary">Excel</a>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;">
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
                    <div class="st-table-wrapper">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th style="width:90px;">
                                        <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                            <span>Type</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="type" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger" data-filter="type" title="Filter Type" style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;">
                                                <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel" data-filter-panel="type" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Type Filter</div>
                                                <select name="slot_type[]" class="st-select st-filter-type-select" style="width:100%;height:34px;">
                                                    <option value="">(All)</option>
                                                    <option value="planned" {{ in_array('planned', $slotTypeFilter ?? [], true) ? 'selected' : '' }}>Planned</option>
                                                    <option value="unplanned" {{ in_array('unplanned', $slotTypeFilter ?? [], true) ? 'selected' : '' }}>Unplanned</option>
                                                </select>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="type">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="type" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Type</div>
                                                <button type="button" class="st-sort-option" data-sort="type" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="type" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
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
                                            <div class="st-filter-panel" data-filter-panel="po" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">PO Filter</div>
                                                <input type="text" name="po" class="st-input" placeholder="Search PO..." value="{{ $po ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="po">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="po" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort PO</div>
                                                <button type="button" class="st-sort-option" data-sort="po" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="po" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
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
                                            <div class="st-filter-panel" data-filter-panel="ticket" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Ticket Filter</div>
                                                <input type="text" name="ticket" class="st-input" placeholder="Search Ticket..." value="{{ $ticket ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="ticket">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="ticket" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Ticket</div>
                                                <button type="button" class="st-sort-option" data-sort="ticket" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="ticket" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">MAT DOC</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" data-type="text" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="mat_doc" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel" data-filter-panel="mat_doc" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">MAT DOC Filter</div>
                                                <input type="text" name="mat_doc" class="st-input" placeholder="Search MAT DOC..." value="{{ $mat_doc ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="mat_doc">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="mat_doc" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort MAT DOC</div>
                                                <button type="button" class="st-sort-option" data-sort="mat_doc" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="mat_doc" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                            <span>Vendor</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger" data-filter="vendor" title="Filter Vendor" style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;">
                                                <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel" data-filter-panel="vendor" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Vendor Filter</div>
                                                <input type="text" name="vendor" class="st-input" placeholder="Search Vendor..." value="{{ $vendor ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="vendor">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="vendor" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Vendor</div>
                                                <button type="button" class="st-sort-option" data-sort="vendor" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="vendor" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                            <span>Warehouse</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger" data-filter="warehouse" title="Filter Warehouse" style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;">
                                                <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel" data-filter-panel="warehouse" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Warehouse Filter</div>
                                                <select name="warehouse_id[]" class="st-select st-filter-warehouse-select" style="width:100%;height:34px;">
                                                    <option value="">(All)</option>
                                                    @foreach ($warehouses as $wh)
                                                        <option value="{{ $wh->id }}" {{ in_array((string) $wh->id, array_map('strval', $warehouseFilter ?? []), true) ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="warehouse">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="warehouse" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Warehouse</div>
                                                <button type="button" class="st-sort-option" data-sort="warehouse" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="warehouse" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                            <span>Direction</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger" data-filter="direction" title="Filter Direction" style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;">
                                                <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel" data-filter-panel="direction" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Direction Filter</div>
                                                <select name="direction[]" class="st-select st-filter-direction-select" style="width:100%;height:34px;">
                                                    <option value="">(All)</option>
                                                    <option value="inbound" {{ in_array('inbound', $directionFilter ?? [], true) ? 'selected' : '' }}>Inbound</option>
                                                    <option value="outbound" {{ in_array('outbound', $directionFilter ?? [], true) ? 'selected' : '' }}>Outbound</option>
                                                </select>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="direction">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="direction" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Direction</div>
                                                <button type="button" class="st-sort-option" data-sort="direction" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="direction" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
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
                                            <div class="st-filter-panel" data-filter-panel="arrival_presence" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:260px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Arrival Date</div>
                                                <input type="text" name="arrival_date_range" id="arrival_date_range" class="st-input" placeholder="Select Arrival Date Range" readonly style="cursor:pointer;width:100%;">
                                                <input type="hidden" name="arrival_date_from" id="arrival_date_from" value="{{ $arrival_date_from ?? '' }}">
                                                <input type="hidden" name="arrival_date_to" id="arrival_date_to" value="{{ $arrival_date_to ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="arrival_presence">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="arrival" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Arrival</div>
                                                <button type="button" class="st-sort-option" data-sort="arrival" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    Newest
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="arrival" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Oldest
                                                </button>
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
                                            <div class="st-filter-panel" data-filter-panel="lead_time" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Lead Time (min)</div>
                                                <div style="display:flex;flex-direction:column;gap:8px;">
                                                    <div>
                                                        <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Min</div>
                                                        <input type="number" name="lead_time_min" class="st-input" placeholder="0" value="{{ $lead_time_min ?? '' }}">
                                                    </div>
                                                    <div>
                                                        <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Max</div>
                                                        <input type="number" name="lead_time_max" class="st-input" placeholder="999" value="{{ $lead_time_max ?? '' }}">
                                                    </div>
                                                </div>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="lead_time">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="lead_time" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Lead Time</div>
                                                <button type="button" class="st-sort-option" data-sort="lead_time" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    Fastest
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="lead_time" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Slowest
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                            <span>Target Status</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="target_status" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger" data-filter="target_status" title="Filter Target Status" style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;">
                                                <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel" data-filter-panel="target_status" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Target Status Filter</div>
                                                <select name="target_status[]" class="st-select st-filter-target-status-select" style="width:100%;height:34px;">
                                                    <option value="">(All)</option>
                                                    <option value="achieve" {{ in_array('achieve', $targetStatusFilter ?? [], true) ? 'selected' : '' }}>Achieve</option>
                                                    <option value="not_achieve" {{ in_array('not_achieve', $targetStatusFilter ?? [], true) ? 'selected' : '' }}>Not Achieve</option>
                                                </select>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="target_status">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="target_status" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Target Status</div>
                                                <button type="button" class="st-sort-option" data-sort="target_status" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="target_status" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Z-A
                                                </button>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                            <span>ARRIVAL STATUS</span>
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="late" data-type="number" title="Sort">⇅</button>
                                            <button type="button" class="st-btn st-btn--sm st-btn--ghost st-filter-trigger" data-filter="late" title="Filter Late" style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;">
                                                <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                            </button>
                                            <div class="st-filter-panel" data-filter-panel="late" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Late Filter</div>
                                                <select name="late[]" class="st-select st-filter-late-select" style="width:100%;height:34px;">
                                                    <option value="">(All)</option>
                                                    <option value="on_time" {{ in_array('on_time', $lateFilter ?? [], true) ? 'selected' : '' }}>On Time</option>
                                                    <option value="late" {{ in_array('late', $lateFilter ?? [], true) ? 'selected' : '' }}>Late</option>
                                                </select>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="late">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="late" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort Late</div>
                                                <button type="button" class="st-sort-option" data-sort="late" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    Terkecil
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="late" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                    Terbesar
                                                </button>
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
                                            <div class="st-filter-panel" data-filter-panel="user" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">User Filter</div>
                                                <input type="text" name="user" class="st-input" placeholder="Search User..." value="{{ $user ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="user">Clear</button>
                                                </div>
                                            </div>
                                            <div class="st-sort-panel" data-sort-panel="user" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Sort User</div>
                                                <button type="button" class="st-sort-option" data-sort="user" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                    A-Z
                                                </button>
                                                <button type="button" class="st-sort-option" data-sort="user" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
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
                                                <div style="font-size:10px;color:#6b7280;margin-top:2px;line-height:1.3;">
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
                                        <td>{{ $r->created_by_username ?? $r->created_by_nik ?? '-' }}</td>
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
                                        <td colspan="13" style="text-align:center;color:#6b7280;padding:16px 8px;">No Transactions Found</td>
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

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr?v=2.0"></script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('transactions-filter-form');
    if (!form) return;

    var isLoading = false;
    var excelLink = document.getElementById('transactions-excel-link');
    var openPanel = null;
    var tableBody = form.querySelector('tbody');

    function setLoading(loading) {
        isLoading = loading;
        if (tableBody) {
            tableBody.style.opacity = loading ? '0.5' : '';
        }
    }

    function buildQueryStringFromForm() {
        var fd = new FormData(form);
        var params = new URLSearchParams();
        fd.forEach(function (value, key) {
            if (value === '' || value === null || typeof value === 'undefined') return;
            params.append(key, value);
        });
        return params.toString();
    }

    function updateExcelLink() {
        if (!excelLink) return;
        var qs = buildQueryStringFromForm();
        var params = new URLSearchParams(qs);
        params.set('export', 'excel');
        params.set('page_size', 'all');
        excelLink.href = window.location.pathname + '?' + params.toString();
    }

    function syncFormFromUrl() {
        var params = new URLSearchParams(window.location.search);
        Array.prototype.slice.call(form.elements).forEach(function (el) {
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

    function ajaxReload(pushState) {
        if (isLoading) return;
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
                var newForm = doc.getElementById('transactions-filter-form');
                var newTbody = newForm ? newForm.querySelector('tbody') : null;
                if (tableBody && newTbody) {
                    tableBody.innerHTML = newTbody.innerHTML;
                }
                updateExcelLink();
                if (pushState) {
                    window.history.pushState(null, '', url);
                }
            })
            .catch(function () {
                window.location.href = url;
            })
            .finally(function () {
                setLoading(false);
            });
    }

    window.ajaxReload = ajaxReload;

    function applyLocalFilter(term) {
        if (!tableBody) return;
        var q = (term || '').toLowerCase().trim();
        var rows = tableBody.querySelectorAll('tr');

        if (!q) {
            rows.forEach(function (tr) { tr.style.display = ''; });
            return;
        }

        rows.forEach(function (tr) {
            var text = (tr.textContent || '').toLowerCase();
            tr.style.display = text.indexOf(q) !== -1 ? '' : 'none';
        });
    }

    function closeOpenPanel() {
        if (openPanel) {
            openPanel.style.display = 'none';
            openPanel = null;
        }
    }

    document.addEventListener('click', function () {
        closeOpenPanel();
    });

    // NOTE: Filter panel toggle/clear/indicator handled globally in resources/js/main.js
    // Mark panels as fixed-position so they will be positioned above sticky table headers.
    try {
        var formForPanels = document.getElementById('transactions-filter-form');
        if (formForPanels) {
            formForPanels.querySelectorAll('.st-filter-panel').forEach(function (p) {
                if (!p) return;
                p.setAttribute('data-st-position', 'fixed');
            });
        }
    } catch (e) {}

    var searchInput = document.querySelector('input[name="q"]');
    var suggestionBox = document.getElementById('transaction-search-suggestions');
    var suggestUrl = "{{ route('reports.search_suggestions') }}";

    if (searchInput && suggestionBox) {
        function hideSuggestions() {
            suggestionBox.style.display = 'none';
            suggestionBox.innerHTML = '';
        }

        document.addEventListener('keydown', function (e) {
            var isCtrlF = (e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'F');
            if (isCtrlF) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        window.selectSuggestion = function (text) {
            searchInput.value = text;
            applyLocalFilter(text);
            hideSuggestions();
            ajaxReload(true);
        };

        searchInput.addEventListener('keyup', function (e) {
            var value = searchInput.value || '';
            applyLocalFilter(value);
            var trimmed = value.trim();

            if (e.key === 'Enter') {
                hideSuggestions();
                ajaxReload(true);
                return;
            }

            if (trimmed.length === 0) {
                hideSuggestions();
                return;
            }

            fetch(suggestUrl + '?q=' + encodeURIComponent(trimmed), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.length > 0) {
                        var html = '';
                        data.forEach(function (item) {
                            html += '<div style="padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;" onclick="selectSuggestion(\'' + String(item.text || '').replace(/'/g, "\\'") + '\')">' + (item.highlighted || item.text || '') + '</div>';
                        });
                        suggestionBox.innerHTML = html;
                        suggestionBox.style.display = 'block';
                    } else {
                        suggestionBox.innerHTML = '<div style="padding:6px 8px;color:#6b7280;">No suggestions for "' + trimmed.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '"</div>';
                        suggestionBox.style.display = 'block';
                    }
                })
                .catch(function () {
                    suggestionBox.innerHTML = '<div style="padding:6px 8px;color:#6b7280;">Error loading suggestions</div>';
                    suggestionBox.style.display = 'block';
                });
        });

        document.addEventListener('click', function (e) {
            var isInside = e.target === searchInput || (e.target.closest && e.target.closest('#transaction-search-suggestions'));
            if (!isInside) {
                hideSuggestions();
            }
        });
    }

    updateExcelLink();

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxReload(true);
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        // Auto-apply on select change
        if (t.matches('select[form="transactions-filter-form"]')) {
            ajaxReload(true);
            return;
        }
        if (t.matches('input[name="page_size"][form="transactions-filter-form"]')) {
            ajaxReload(true);
            return;
        }
    });

    // Add Enter key support for text inputs
    document.addEventListener('keydown', function (e) {
        var t = e.target;
        if (!t) return;
        if (e.key === 'Enter' && t.matches('input[type="text"][form="transactions-filter-form"], input[type="number"][form="transactions-filter-form"]')) {
            e.preventDefault();
            ajaxReload(true);
            return;
        }
    });

    window.addEventListener('popstate', function () {
        syncFormFromUrl();
        ajaxReload(false);
    });

    // Date range logic
    var dateRangeInput = document.getElementById('date_range');
    var arrivalDateRangeInput = document.getElementById('arrival_date_range');
    var dateFromInput = document.getElementById('date_from');
    var dateToInput = document.getElementById('date_to');
    var arrivalDateFromInput = document.getElementById('arrival_date_from');
    var arrivalDateToInput = document.getElementById('arrival_date_to');
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function formatDate(dObj) {
        if (!dObj) return '';
        var Y = dObj.getFullYear();
        var M = ('0' + (dObj.getMonth() + 1)).slice(-2);
        var D = ('0' + dObj.getDate()).slice(-2);
        return Y + '-' + M + '-' + D;
    }

    // Helper to format date for display (d M Y)
    function formatDateDisplay(dateStr) {
        if (!dateStr) return '';
        try {
            var date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            var day = date.getDate();
            var month = months[date.getMonth()];
            var year = date.getFullYear();
            return day + " " + month + " " + year;
        } catch (e) {
            return dateStr;
        }
    }

    if (dateRangeInput && dateFromInput && dateToInput) {
        // Initialize value if present
        if (dateFromInput.value && dateToInput.value) {
            dateRangeInput.value = formatDateDisplay(dateFromInput.value) + ' to ' + formatDateDisplay(dateToInput.value);
        } else if (dateFromInput.value) {
             dateRangeInput.value = formatDateDisplay(dateFromInput.value);
        }

        var drp = flatpickr(dateRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            disableMobile: true,
            allowInput: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                if (holidayData[dateStr]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = holidayData[dateStr];
                }
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    var s = formatDate(selectedDates[0]);
                    var e = formatDate(selectedDates[1]);
                    // Update display
                    dateRangeInput.value = formatDateDisplay(s) + ' to ' + formatDateDisplay(e);
                    // Update hidden
                    dateFromInput.value = s;
                    dateToInput.value = e;
                    ajaxReload(true);
                } else if (selectedDates.length === 1) {
                     var s = formatDate(selectedDates[0]);
                     dateRangeInput.value = formatDateDisplay(s);
                     dateFromInput.value = s;
                     dateToInput.value = s;
                     ajaxReload(true);
                } else {
                    dateRangeInput.value = '';
                    dateFromInput.value = '';
                    dateToInput.value = '';
                    ajaxReload(true);
                }
            }
        });
    }

    // Initialize Arrival Date Range
    if (arrivalDateRangeInput && arrivalDateFromInput && arrivalDateToInput) {
         if (arrivalDateFromInput.value && arrivalDateToInput.value) {
            arrivalDateRangeInput.value = formatDateDisplay(arrivalDateFromInput.value) + ' to ' + formatDateDisplay(arrivalDateToInput.value);
        } else if (arrivalDateFromInput.value) {
             arrivalDateRangeInput.value = formatDateDisplay(arrivalDateFromInput.value);
        }

        var adrp = flatpickr(arrivalDateRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            disableMobile: true,
            allowInput: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                if (holidayData[dateStr]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = holidayData[dateStr];
                }
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    var s = formatDate(selectedDates[0]);
                    var e = formatDate(selectedDates[1]);
                    arrivalDateRangeInput.value = formatDateDisplay(s) + ' to ' + formatDateDisplay(e);
                    arrivalDateFromInput.value = s;
                    arrivalDateToInput.value = e;
                    ajaxReload(true);
                } else if (selectedDates.length === 1) {
                    var s = formatDate(selectedDates[0]);
                    arrivalDateRangeInput.value = formatDateDisplay(s);
                    arrivalDateFromInput.value = s;
                    arrivalDateToInput.value = s;
                    ajaxReload(true);
                } else {
                    arrivalDateRangeInput.value = '';
                    arrivalDateFromInput.value = '';
                    arrivalDateToInput.value = '';
                    ajaxReload(true);
                }
            }
        });
    }

    // Reset all filters button
    document.getElementById('clear-date-range').addEventListener('click', function() {
        // Clear all active filter indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-filter', 'st-filter-header--active-filter');
        });
        document.querySelectorAll('.st-filter-trigger').forEach(function(btn) {
            btn.classList.remove('st-filter-trigger--active');
        });

        // Clear all active sort indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-sort', 'st-filter-header--active-sort');
        });
        document.querySelectorAll('.st-sort-trigger').forEach(function(btn) {
            btn.classList.remove('st-sort-trigger--active');
        });

        // Redirect to clean page with all filters reset
        window.location.href = "{{ route('reports.transactions') }}";
    });

    // Close panels when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.st-colhead') && !e.target.closest('.st-filter-header')) {
            document.querySelectorAll('.st-filter-panel').forEach(p => p.style.display = 'none');
            document.querySelectorAll('.st-sort-panel').forEach(p => p.style.display = 'none');
        }
    });
});
</script>
@endpush
