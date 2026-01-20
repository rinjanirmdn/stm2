@extends('layouts.app')

@section('title', 'Unplanned Transactions - Slot Time Management')
@section('page_title', 'Unplanned Transactions')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="padding:12px;">
            <div class="st-form-row" style="align-items:flex-end;">
                <div class="st-form-field" style="flex:1;min-width:260px;position:relative;">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        name="q"
                        form="unplanned-filter-form"
                        class="st-input"
                        placeholder="PO/DO, MAT DOC, Vendor, etc"
                        value="{{ request('q') }}"
                    >
                </div>
                <div class="st-form-field" style="max-width:120px;">
                    <label class="st-label">Show</label>
                    <select name="page_size" form="unplanned-filter-form" class="st-select">
                        @foreach (['10','25','50','100','all'] as $ps)
                            <option value="{{ $ps }}" {{ request('page_size', '50') === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="flex:0 0 auto;">
                    <button type="submit" form="unplanned-filter-form" class="st-btn st-btn--secondary">Search</button>
                    <a href="{{ route('unplanned.index') }}?sort=reset" class="st-btn st-btn--secondary">Reset</a>
                    @can('unplanned.create')
                    <a href="{{ route('unplanned.create') }}" class="st-btn st-btn--primary">Create Unplanned</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;">
                <form method="GET" id="unplanned-filter-form" data-multi-sort="1" action="{{ route('unplanned.index') }}">
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
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">PO/DO</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po_number" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="po" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="po" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">PO/DO Filter</div>
                                            <input type="text" name="po_number" form="unplanned-filter-form" class="st-input" placeholder="Cari PO/DO..." value="{{ request('po_number') }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="po">Clear</button>
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
                                            <input type="text" name="mat_doc" form="unplanned-filter-form" class="st-input" placeholder="Cari MAT DOC..." value="{{ request('mat_doc') }}">
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor_name" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="vendor" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="vendor" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Vendor Filter</div>
                                            <input type="text" name="vendor" form="unplanned-filter-form" class="st-input" placeholder="Cari vendor..." value="{{ request('vendor') }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="vendor">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Warehouse / Gate</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse_name" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="whgate" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="whgate" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Warehouse / Gate Filter</div>
                                            <div style="display:flex;gap:8px;align-items:flex-start;">
                                                <div style="flex:1;min-width:140px;">
                                                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Warehouse</div>
                                                    <select name="warehouse" form="unplanned-filter-form" class="st-select" style="width:100%;">
                                                        <option value="">(All)</option>
                                                        @foreach($warehouses as $wh)
                                                            <option value="{{ $wh->name }}" {{ request('warehouse') === $wh->name ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div style="flex:1;min-width:80px;">
                                                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">Gate</div>
                                                    <select name="gate" form="unplanned-filter-form" class="st-select" style="width:100%;">
                                                        <option value="">(All)</option>
                                                        @foreach($gates as $gate)
                                                            <option value="{{ $gate }}" {{ request('gate') === $gate ? 'selected' : '' }}>{{ $gate }}</option>
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
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Direction</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="direction" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="direction" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:160px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Direction</div>
                                            <select name="direction" form="unplanned-filter-form" class="st-select" style="width:100%;height:34px;">
                                                <option value="" {{ !in_array(request('direction'), ['inbound', 'outbound']) ? 'selected' : '' }}>(All)</option>
                                                <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                                                <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="direction">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Status</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="status" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="status" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:180px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Status</div>
                                            <select name="status" form="unplanned-filter-form" class="st-select" style="width:100%;height:34px;">
                                                <option value="" {{ !in_array(request('status'), ['waiting', 'completed']) ? 'selected' : '' }}>(All)</option>
                                                <option value="waiting" {{ request('status') === 'waiting' ? 'selected' : '' }}>Waiting</option>
                                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="status">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Arrival</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="arrival_time" data-type="date" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="arrival" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="arrival" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:280px;max-height:260px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Arrival Range</div>
                                            <input type="text" id="unplanned_arrival_range" name="arrival_range" form="unplanned-filter-form" class="st-input" placeholder="Pilih rentang tanggal" value="{{ (request('arrival_from') && request('arrival_to')) ? (request('arrival_from').' to '.request('arrival_to')) : ((request('arrival_from') || request('arrival_to')) ? (request('arrival_from') ?: request('arrival_to')) : '') }}" readonly style="cursor:pointer;">
                                            <input type="hidden" name="arrival_from" form="unplanned-filter-form" value="{{ request('arrival_from') }}">
                                            <input type="hidden" name="arrival_to" form="unplanned-filter-form" value="{{ request('arrival_to') }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="arrival">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">SJ</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="sj_complete_number" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="sj" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="sj" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:9999;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">SJ Filter</div>
                                            <input type="text" name="sj_number" form="unplanned-filter-form" class="st-input" placeholder="Cari SJ..." value="{{ request('sj_number') }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="sj">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @if ($unplannedSlots->isEmpty())
                    <tr>
                        <td colspan="10" style="text-align:center;color:#6b7280;padding:12px 8px;">No unplanned transactions found</td>
                    </tr>
                @else
                    @foreach ($unplannedSlots as $idx => $slot)
                        @php
                            $gateNumber = $slot->actual_gate_number ?? '';
                            if ($gateNumber === '' || $gateNumber === null) {
                                $label = $slot->warehouse_name ?? '';
                            } else {
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($slot->warehouse_code ?? '', $gateNumber);
                                $label = trim(($slot->warehouse_name ?? '') !== '' ? (($slot->warehouse_name ?? '') . ' - ' . $gateLabel) : $gateLabel);
                            }

                            $st = (string) ($slot->status ?? '');
                            $stClass = strtolower(str_replace(' ', '_', $st));
                            $stLabel = ucwords(str_replace('_', ' ', $st));
                            if ($st === 'arrived') {
                                $stClass = 'waiting';
                                $stLabel = 'Waiting';
                            }
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $slot->truck_number ?? '' }}</td>
                            <td>{{ !empty($slot->mat_doc) ? $slot->mat_doc : '-' }}</td>
                            <td>{{ $slot->vendor_name ?? '-' }}</td>
                            <td>{{ $label !== '' ? $label : '-' }}</td>
                            <td>
                                @php $dir = strtolower($slot->direction ?? ''); @endphp
                                @if($dir === 'inbound')
                                    <span class="st-badge-modern st-badge-modern--inbound">Inbound</span>
                                @elseif($dir === 'outbound')
                                    <span class="st-badge-modern st-badge-modern--outbound">Outbound</span>
                                @else
                                    {{ strtoupper($slot->direction ?? '') }}
                                @endif
                            </td>
                            @php
                                $badgeMap = [
                                    'scheduled' => 'bg-scheduled',
                                    'arrived' => 'bg-waiting',
                                    'waiting' => 'bg-waiting',
                                    'in_progress' => 'bg-in_progress',
                                    'completed' => 'bg-completed',
                                    'cancelled' => 'bg-danger',
                                ];
                                $badgeClass = $badgeMap[$st] ?? 'bg-secondary';
                            @endphp
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $stLabel }}</span>
                            </td>
                            <td>
                                @if (!empty($slot->arrival_time))
                                    {{ \Carbon\Carbon::parse((string) $slot->arrival_time)->format('d M Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ !empty($slot->sj_complete_number) ? $slot->sj_complete_number : '-' }}</td>
                            <td>
                                <div class="tw-actionbar">
                                    @if (($slot->status ?? '') === 'waiting')
                                        <a href="{{ route('unplanned.start', ['slotId' => $slot->id]) }}" class="tw-action tw-action--primary" data-tooltip="Start" aria-label="Start">
                                            <i class="fa-solid fa-play"></i>
                                        </a>
                                    @elseif (($slot->status ?? '') === 'in_progress')
                                        <a href="{{ route('unplanned.complete', ['slotId' => $slot->id]) }}" class="tw-action tw-action--primary" data-tooltip="Complete" aria-label="Complete">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    @endif

                                    @unless(optional(auth()->user())->hasRole('Operator'))
                                    @can('unplanned.edit')
                                    <a href="{{ route('unplanned.edit', ['slotId' => $slot->id]) }}" class="tw-action" data-tooltip="Edit" aria-label="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    @endcan
                                    @endunless

                                    <a href="{{ route('unplanned.show', ['slotId' => $slot->id]) }}" class="tw-action" data-tooltip="View" aria-label="View">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr range for arrival date
        var arrivalRangeInput = document.querySelector('input#unplanned_arrival_range');
        if (arrivalRangeInput) {
            flatpickr(arrivalRangeInput, {
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
                        // Only auto-submit when we have a complete range
                        setTimeout(function() {
                            document.getElementById('unplanned-filter-form').submit();
                        }, 100);
                    } else if (selectedDates.length === 1) {
                        fromInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                        toInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                        // Don't auto-submit for single date in range mode, let user complete the range
                    }
                },
                onClose: function(selectedDates, dateStr, instance) {
                    // Submit form when user closes the date picker
                    if (selectedDates.length > 0) {
                        setTimeout(function() {
                            document.getElementById('unplanned-filter-form').submit();
                        }, 100);
                    }
                }
            });
        }

        var filterForm = document.getElementById('unplanned-filter-form');
        if (!filterForm) return;
        // NOTE: Filter panel toggle/clear/sort/indicator handled globally in resources/js/main.js
        // Mark panels as fixed-position so they will be positioned above sticky table headers.
        try {
            filterForm.querySelectorAll('.st-filter-panel').forEach(function (p) {
                if (!p) return;
                p.setAttribute('data-st-position', 'fixed');
            });
        } catch (e) {}
    });
    </script>
    @endpush
@endsection
