@extends('layouts.app')

@section('title', 'Slots - Slot Time Management')
@section('page_title', 'Slots')

@section('content')
    <div class="tw-slots">
    <div class="st-card tw-card" style="margin-bottom:12px;">
        <div class="tw-card__body">
            <div class="st-form-row" style="align-items:flex-end;">
                <div class="st-form-field" style="flex:1;min-width:260px;position:relative;">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        name="q"
                        form="slot-filter-form"
                        class="st-input"
                        placeholder="Truck, MAT DOC, Vendor, etc"
                        value="{{ $search }}"
                    >
                    <div id="slot-search-suggestions" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:30;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;max-height:220px;overflow:auto;min-width:260px;"></div>
                </div>
                <div class="st-form-field" style="max-width:120px;">
                    <label class="st-label">Show</label>
                    <select name="page_size" form="slot-filter-form" class="st-select">
                        @foreach (['10','25','50','100','all'] as $ps)
                            <option value="{{ $ps }}" {{ $pageSize === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="flex:0 0 auto;">
                    <button type="submit" form="slot-filter-form" class="st-btn st-btn--secondary">Search</button>
                    <a href="{{ route('slots.index') }}" class="st-btn st-btn--secondary">Reset</a>
                    @unless(optional(auth()->user())->hasRole('Operator'))
                    @can('slots.create')
                    <a href="{{ route('slots.create') }}" class="st-btn st-btn--primary">Create Slot</a>
                    @endcan
                    @endunless
                </div>
            </div>
        </div>
    </div>

    <section class="st-row" style="margin:0;padding:0;">
        <div class="st-col-12" style="display:flex;flex-direction:column;">
            <div class="st-card tw-card tw-card--table" style="margin:0;">
                <form method="GET" id="slot-filter-form" action="{{ route('slots.index') }}" data-multi-sort="1">
                @php
                    $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                    $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                @endphp
                @foreach ($sortsArr as $i => $s)
                    @php $d = $dirsArr[$i] ?? 'asc'; @endphp
                    <input type="hidden" name="sort[]" value="{{ $s }}">
                    <input type="hidden" name="dir[]" value="{{ $d }}">
                @endforeach
                <div class="st-table-wrapper tw-table-wrap">
                    <table class="st-table tw-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">PO</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="truck" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="truck" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">PO Filter</div>
                                            <input type="text" name="truck" form="slot-filter-form" class="st-input" placeholder="Cari PO..." value="{{ $truck ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="truck">Clear</button>
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
                                        <div class="st-filter-panel" data-filter-panel="mat_doc" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">MAT DOC Filter</div>
                                            <input type="text" name="mat_doc" form="slot-filter-form" class="st-input" placeholder="Cari MAT DOC..." value="{{ $mat_doc ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="mat_doc">Clear</button>
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
                                        <div class="st-filter-panel" data-filter-panel="vendor" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Vendor Filter</div>
                                            <input type="text" name="vendor" form="slot-filter-form" class="st-input" placeholder="Cari vendor..." value="{{ $vendor ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="vendor">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                        <span>Warehouse / Gate</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse" title="Sort">⇅</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="whgate"
                                            title="Filter Warehouse / Gate"
                                            style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;"
                                        >
                                            <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel" data-filter-panel="whgate" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:260px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Warehouse / Gate Filter</div>
                                            <div style="display:flex;gap:8px;align-items:flex-start;">
                                                <div style="flex:1;min-width:140px;">
                                                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Warehouse</div>
                                                    <select name="warehouse_id[]" form="slot-filter-form" class="st-select st-filter-warehouse-select" style="width:100%;height:34px;">
                                                        <option value="">(All)</option>
                                                        @foreach ($warehouses as $wh)
                                                            <option value="{{ $wh->id }}" {{ in_array((string)$wh->id, array_map('strval', $warehouseFilter), true) ? 'selected' : '' }}>
                                                                {{ $wh->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div style="flex:1;min-width:100px;">
                                                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Gate</div>
                                                    <select name="gate[]" form="slot-filter-form" class="st-select st-filter-gate-select" style="width:100%;height:34px;">
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
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="whgate">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                        <span>Direction</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" title="Sort">⇅</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="direction"
                                            title="Filter Direction"
                                            style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;"
                                        >
                                            <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel" data-filter-panel="direction" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Direction Filter</div>
                                            <select name="direction[]" form="slot-filter-form" class="st-select st-filter-direction-select" style="width:100%;height:34px;">
                                                <option value="">(All)</option>
                                                <option value="inbound" {{ in_array('inbound', $directionFilter, true) ? 'selected' : '' }}>Inbound</option>
                                                <option value="outbound" {{ in_array('outbound', $directionFilter, true) ? 'selected' : '' }}>Outbound</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="direction">Clear</button>
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
                                        <div class="st-filter-panel" data-filter-panel="planned_start" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:280px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">ETA Range</div>
                                            <input type="text" id="planned_start_range" name="planned_start_range" form="slot-filter-form" class="st-input" placeholder="Pilih rentang tanggal" value="{{ ($date_from ?? '') && ($date_to ?? '') ? ($date_from.' to '.$date_to) : '' }}" readonly style="cursor:pointer;">
                                            <input type="hidden" name="date_from" form="slot-filter-form" value="{{ $date_from ?? '' }}">
                                            <input type="hidden" name="date_to" form="slot-filter-form" value="{{ $date_to ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="planned_start">Clear</button>
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
                                        <div class="st-filter-panel" data-filter-panel="arrival_presence" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:280px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Arrival Date Filter</div>
                                            <input type="text" id="arrival_date_range" name="arrival_date_range" form="slot-filter-form" class="st-input" placeholder="Pilih rentang tanggal" value="{{ ($arrival_from ?? '') && ($arrival_to ?? '') ? ($arrival_from.' to '.$arrival_to) : '' }}" readonly style="cursor:pointer;">
                                            <input type="hidden" name="arrival_from" form="slot-filter-form" value="{{ $arrival_from ?? '' }}">
                                            <input type="hidden" name="arrival_to" form="slot-filter-form" value="{{ $arrival_to ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="arrival_presence">Clear</button>
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
                                        <div class="st-filter-panel" data-filter-panel="lead_time" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Lead Time (min)</div>
                                            <div style="display:flex;flex-direction:column;gap:8px;">
                                                <div>
                                                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Min</div>
                                                    <input type="number" name="lead_time_min" form="slot-filter-form" class="st-input" placeholder="0" value="{{ $lead_time_min ?? '' }}">
                                                </div>
                                                <div>
                                                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Max</div>
                                                    <input type="number" name="lead_time_max" form="slot-filter-form" class="st-input" placeholder="999" value="{{ $lead_time_max ?? '' }}">
                                                </div>
                                            </div>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="lead_time">Clear</button>
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
                                        <div class="st-filter-panel" data-filter-panel="target_status" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:220px;max-height:240px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Target Status Filter</div>
                                            <select name="target_status[]" form="slot-filter-form" class="st-select" style="width:100%;height:34px;">
                                                <option value="">(All)</option>
                                                <option value="achieve" {{ in_array('achieve', $targetStatusFilter ?? [], true) ? 'selected' : '' }}>Achieve</option>
                                                <option value="not_achieve" {{ in_array('not_achieve', $targetStatusFilter ?? [], true) ? 'selected' : '' }}>Not Achieve</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="target_status">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                        <span>ARRIVAL STATUS</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="late" title="Sort">⇅</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="late"
                                            title="Filter Late"
                                            style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;"
                                        >
                                            <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel" data-filter-panel="late" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Late Filter</div>
                                            <select name="late[]" form="slot-filter-form" class="st-select st-filter-late-select" style="width:100%;height:34px;">
                                                <option value="">(All)</option>
                                                <option value="on_time" {{ in_array('on_time', $lateFilter, true) ? 'selected' : '' }}>On Time</option>
                                                <option value="late" {{ in_array('late', $lateFilter, true) ? 'selected' : '' }}>Late</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="late">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                        <span>Status</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="status" title="Sort">⇅</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="status"
                                            title="Filter Status"
                                            style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;"
                                        >
                                            <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel" data-filter-panel="status" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:220px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Status Filter</div>
                                            <select name="status[]" form="slot-filter-form" class="st-select st-filter-status-select" style="width:100%;height:34px;">
                                                <option value="">(All)</option>
                                                @foreach (['scheduled','waiting','in_progress','completed','cancelled'] as $st)
                                                    <option value="{{ $st }}" {{ in_array($st, $statusFilter, true) ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $st)) }}</option>
                                                @endforeach
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="status">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-filter-header" style="display:inline-flex;align-items:center;gap:4px;position:relative;">
                                        <span>Blocking</span>
                                        <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="blocking" title="Sort">⇅</button>
                                        <button
                                            type="button"
                                            class="st-btn st-btn--sm st-btn--ghost st-filter-trigger"
                                            data-filter="blocking"
                                            title="Filter Blocking"
                                            style="padding:1px 6px;min-width:18px;height:18px;background:#f3f4f6;border-color:#d1d5db;color:#4b5563;border-radius:999px;"
                                        >
                                            <span style="font-size:9px;line-height:1;">&#x25BC;</span>
                                        </button>
                                        <div class="st-filter-panel" data-filter-panel="blocking" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:220px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Blocking Filter</div>
                                            <select name="blocking[]" form="slot-filter-form" class="st-select st-filter-blocking-select" style="width:100%;height:34px;">
                                                <option value="">(All)</option>
                                                <option value="low" {{ in_array('low', $blockingFilter ?? [], true) ? 'selected' : '' }}>Low</option>
                                                <option value="medium" {{ in_array('medium', $blockingFilter ?? [], true) ? 'selected' : '' }}>Medium</option>
                                                <option value="high" {{ in_array('high', $blockingFilter ?? [], true) ? 'selected' : '' }}>High</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="blocking">Clear</button>
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
                                    'scheduled' => 'bg-scheduled',
                                    'arrived' => 'bg-waiting',
                                    'waiting' => 'bg-waiting',
                                    'in_progress' => 'bg-in_progress',
                                    'completed' => 'bg-completed',
                                    'cancelled' => 'bg-danger',
                                ];
                                $badgeClass = $badgeMap[$status] ?? 'bg-secondary';
                            @endphp
                            <tr>
                                <td>{{ $rowNumber }}</td>
                                <td>{{ $row->truck_number }}</td>
                                <td>{{ $row->mat_doc ?? '-' }}</td>
                                <td>{{ $row->vendor_name ?? '-' }}</td>
                                <td>{{ $whGateLabel }}</td>
                                <td>
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
                                <td>{{ $fmt($row->planned_start ?? null) }}</td>
                                <td>{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</td>
                                <td>{{ !empty($row->arrival_time) ? $fmt($row->arrival_time) : '-' }}</td>
                                <td>
                                    @if ($leadTimeMinutes !== null)
                                        @php
                                            $m = (int) $leadTimeMinutes;
                                            $h = $m / 60;
                                        @endphp
                                        {{ $m }} min
                                        @if ($h >= 1)
                                            ({{ rtrim(rtrim(number_format($h, 2), '0'), '.') }} h)
                                        @endif
                                        <div style="font-size:11px;color:#6b7280;margin-top:2px;line-height:1.3;">
                                            @if ($waitingMinutes !== null)
                                                <div>Waiting: {{ (int) $waitingMinutes }} min</div>
                                            @endif
                                            @if ($processMinutes !== null)
                                                <div>Process: {{ (int) $processMinutes }} min</div>
                                            @endif
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
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
                                <td>
                                    @if ($lateDisplay === 'late')
                                        <span class="st-table__status-badge st-status-late">Late</span>
                                    @elseif ($lateDisplay === 'on_time')
                                        <span class="st-table__status-badge st-status-on-time">On Time</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_',' ', $status)) }}</span>
                                </td>
                                <td>
                                    <span class="st-table__status-badge {{ $blockingClass }}">{{ $blockingLabel }}</span>
                                </td>
                                <td>
                                    <div class="tw-actionbar">
                                        @if ($slotTypeVal === 'planned')
                                            @if ($status === 'scheduled')
                                                @unless(optional(auth()->user())->hasRole('Operator'))
                                                @can('slots.edit')
                                                <a href="{{ route('slots.edit', ['slotId' => $row->id]) }}" class="tw-action" data-tooltip="Edit" aria-label="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                @endcan
                                                @endunless
                                            @endif

                                            @if (!$hasArrival && $status === 'scheduled')
                                                <a href="{{ route('slots.arrival', ['slotId' => $row->id]) }}" class="tw-action" data-tooltip="Arrival" aria-label="Arrival">
                                                    <i class="fa-solid fa-truck"></i>
                                                </a>
                                            @elseif (in_array($status, ['waiting'], true))
                                                <a href="{{ route('slots.start', ['slotId' => $row->id]) }}" class="tw-action tw-action--primary" data-tooltip="Start" aria-label="Start">
                                                    <i class="fa-solid fa-play"></i>
                                                </a>
                                            @endif
                                        @endif

                                        @if ($status === 'in_progress')
                                            <a href="{{ route('slots.complete', ['slotId' => $row->id]) }}" class="tw-action tw-action--primary" data-tooltip="Complete" aria-label="Complete">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                        @endif

                                        @if ($status === 'scheduled')
                                            @can('slots.cancel')
                                            <a href="{{ route('slots.cancel', ['slotId' => $row->id]) }}" class="tw-action tw-action--danger btn-cancel-slot" data-tooltip="Cancel" aria-label="Cancel">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                            @endcan
                                        @endif

                                        @if (!empty($row->ticket_number) && in_array($status, ['scheduled', 'waiting', 'in_progress'], true))
                                            @unless(optional(auth()->user())->hasRole('Operator'))
                                            @can('slots.ticket')
                                            <a href="{{ route('slots.ticket', ['slotId' => $row->id]) }}" class="tw-action" data-tooltip="Print Ticket" aria-label="Print Ticket" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                            @endcan
                                            @endunless
                                        @endif

                                        <a href="{{ route('slots.show', ['slotId' => $row->id]) }}" class="tw-action" data-tooltip="View" aria-label="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" style="text-align:center;color:#6b7280;padding:16px 8px;">No slots found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
        </div>
    </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tooltips handled globally in resources/js/main.js (st-global-tooltip)

    // Initialize Flatpickr for date inputs
    var dateInputs = document.querySelectorAll('input[type="date"][form="slot-filter-form"]');
    dateInputs.forEach(function(input) {
        flatpickr(input, {
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: true,
            theme: 'dark'
        });
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

    function bindCancelConfirm() {
        document.querySelectorAll('.btn-cancel-slot').forEach(function (btn) {
            if (btn.getAttribute('data-confirm-bound') === '1') return;
            btn.setAttribute('data-confirm-bound', '1');
            btn.addEventListener('click', function (e) {
                if (!confirm('Yakin ingin membatalkan slot ini?')) {
                    e.preventDefault();
                }
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
            .catch(function () {
                window.location.href = url;
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

                // Special handling for date range filters - clear related hidden inputs
                if (filterName === 'planned_start') {
                    var dateFromInput = filterForm.querySelector('input[name="date_from"]');
                    var dateToInput = filterForm.querySelector('input[name="date_to"]');
                    var rangeInput = filterForm.querySelector('input[name="planned_start_range"]');
                    if (dateFromInput) dateFromInput.value = '';
                    if (dateToInput) dateToInput.value = '';
                    if (rangeInput) rangeInput.value = '';
                }

                if (filterName === 'arrival_presence') {
                    var arrivalFromInput = filterForm.querySelector('input[name="arrival_from"]');
                    var arrivalToInput = filterForm.querySelector('input[name="arrival_to"]');
                    var rangeInput = filterForm.querySelector('input[name="arrival_date_range"]');
                    if (arrivalFromInput) arrivalFromInput.value = '';
                    if (arrivalToInput) arrivalToInput.value = '';
                    if (rangeInput) rangeInput.value = '';
                }

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
            i.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    ajaxReload(true);
                }
            });
            i.addEventListener('change', function () {
                ajaxReload(true);
            });
        });
    }

    function setupActiveFilters() {
        // Clear all active filter indicators first
        document.querySelectorAll('.st-colhead, .st-filter-header').forEach(function(head) {
            head.classList.remove('st-colhead--active-filter', 'st-filter-header--active-filter');
        });
        document.querySelectorAll('.st-filter-trigger').forEach(function(btn) {
            btn.classList.remove('st-filter-trigger--active');
        });

        // Check each filter field for active values
        var activeFilters = [];

        // Text input filters
        var textFilters = ['truck', 'mat_doc', 'vendor', 'po_number', 'sj_number'];
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

    // Common Flatpickr configuration for consistent styling
    var commonFlatpickrConfig = {
        dateFormat: 'Y-m-d',
        allowInput: true,
        onChange: function(selectedDates, dateStr, instance) {
            // Auto-submit when date is selected
            if (dateStr) {
                document.getElementById('slot-filter-form').submit();
            }
        }
    };

    // Initialize Flatpickr range for planned start
    var plannedStartRangeInput = document.querySelector('input#planned_start_range');
    if (plannedStartRangeInput) {
        flatpickr(plannedStartRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Update hidden fields
                var fromInput = document.querySelector('input[name="date_from"]');
                var toInput = document.querySelector('input[name="date_to"]');
                if (selectedDates.length === 2) {
                    fromInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toInput.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                } else if (selectedDates.length === 1) {
                    fromInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                }
                // Auto-submit
                document.getElementById('slot-filter-form').submit();
            }
        });
    }

    // Initialize Flatpickr range for arrival date
    var arrivalDateRangeInput = document.querySelector('input#arrival_date_range');
    if (arrivalDateRangeInput) {
        flatpickr(arrivalDateRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Update hidden fields
                var fromInput = document.querySelector('input[name="arrival_from"]');
                var toInput = document.querySelector('input[name="arrival_to"]');
                if (selectedDates.length === 2) {
                    fromInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toInput.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                } else if (selectedDates.length === 1) {
                    fromInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                }
                // Auto-submit
                document.getElementById('slot-filter-form').submit();
            }
        });
    }

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
                            html += '<div style="padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;" onclick="selectSuggestion(\'' + String(t).replace(/'/g, "\\'") + '\')">' + h + '</div>';
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
