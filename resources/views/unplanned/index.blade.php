@extends('layouts.app')

@section('title', 'Unplanned Transactions - Slot Time Management')
@section('page_title', 'Unplanned Transactions')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-form-row st-form-row--gap-4 st-items-end">
                <div class="st-form-field st-maxw-260">
                    <label class="st-label">Search</label>
                    <div class="st-relative">
                        <input
                            type="text"
                            name="q"
                            form="unplanned-filter-form"
                            class="st-input"
                            placeholder="PO/DO, MAT DOC, Vendor, Etc"
                            value="{{ request('q') }}"
                        >
                    </div>
                </div>
                <div class="st-form-field st-maxw-120">
                    <label class="st-label">Show</label>
                    <select name="page_size" form="unplanned-filter-form" class="st-select">
                        @foreach (['10','25','50','100','all'] as $ps)
                            <option value="{{ $ps }}" {{ request('page_size', '50') === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-minw-80 st-flex-0 st-flex st-justify-end st-gap-8">
                    <a href="{{ route('unplanned.index') }}?sort=reset" class="st-btn st-btn--outline-primary">Reset</a>
                    @can('unplanned.create')
                    <a href="{{ route('unplanned.create') }}" class="st-btn st-btn--primary">Create Unplanned</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1">
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
                <div class="st-table-wrapper st-table-wrapper--minh-400">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="st-table-col-40">#</th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">PO/DO</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po_number" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="po" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240" data-filter-panel="po">
                                            <div class="st-font-semibold st-mb-6">PO/DO Filter</div>
                                            <input type="text" name="po_number" form="unplanned-filter-form" class="st-input" placeholder="Search PO/DO..." value="{{ request('po_number') }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="po">Clear</button>
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
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240" data-filter-panel="mat_doc">
                                            <div class="st-font-semibold st-mb-6">MAT DOC Filter</div>
                                            <input type="text" name="mat_doc" form="unplanned-filter-form" class="st-input" placeholder="Search MAT DOC..." value="{{ request('mat_doc') }}">
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="vendor_name" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="vendor" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240" data-filter-panel="vendor">
                                            <div class="st-font-semibold st-mb-6">Vendor Filter</div>
                                            <input type="text" name="vendor" form="unplanned-filter-form" class="st-input" placeholder="Search Vendor..." value="{{ request('vendor') }}">
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
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="warehouse_name" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="whgate" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-240" data-filter-panel="whgate">
                                            <div class="st-font-semibold st-mb-6">Warehouse / Gate Filter</div>
                                            <div class="st-flex st-gap-8 st-items-start">
                                                <div class="st-flex-1 st-minw-140">
                                                    <div class="st-text--xs-11 st-font-semibold st-mb-4">Warehouse</div>
                                                    <select name="warehouse" form="unplanned-filter-form" class="st-select st-select--panel">
                                                        <option value="">(All)</option>
                                                        @foreach($warehouses as $wh)
                                                            <option value="{{ $wh->name }}" {{ request('warehouse') === $wh->name ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="st-flex-1 st-minw-80">
                                                    <div class="st-text--xs-11 st-font-semibold st-mb-4">Gate</div>
                                                    <select name="gate" form="unplanned-filter-form" class="st-select st-select--panel">
                                                        <option value="">(All)</option>
                                                        @foreach($gates as $gate)
                                                            <option value="{{ $gate }}" {{ request('gate') === $gate ? 'selected' : '' }}>{{ $gate }}</option>
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
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Direction</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="direction" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="direction" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-160" data-filter-panel="direction">
                                            <div class="st-font-semibold st-mb-6">Direction</div>
                                            <select name="direction" form="unplanned-filter-form" class="st-select st-select--panel">
                                                <option value="" {{ !in_array(request('direction'), ['inbound', 'outbound']) ? 'selected' : '' }}>(All)</option>
                                                <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                                                <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="direction">Clear</button>
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
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-180" data-filter-panel="status">
                                            <div class="st-font-semibold st-mb-6">Status</div>
                                            <select name="status" form="unplanned-filter-form" class="st-select st-select--panel">
                                                <option value="" {{ !in_array(request('status'), ['waiting', 'completed']) ? 'selected' : '' }}>(All)</option>
                                                <option value="waiting" {{ request('status') === 'waiting' ? 'selected' : '' }}>Waiting</option>
                                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="status">Clear</button>
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
                                        <div class="st-filter-panel st-hidden st-top-full st-left-0 st-mt-4 st-minw-280 st-maxh-260" data-filter-panel="arrival">
                                            <div class="st-font-semibold st-mb-6">Arrival Range</div>
                                            <input type="text" id="unplanned_arrival_range" name="arrival_range" form="unplanned-filter-form" class="st-input st-input--cursor" placeholder="Select Date Range" value="{{ (request('arrival_from') && request('arrival_to')) ? (request('arrival_from').' to '.request('arrival_to')) : ((request('arrival_from') || request('arrival_to')) ? (request('arrival_from') ?: request('arrival_to')) : '') }}" readonly>
                                            <input type="hidden" name="arrival_from" form="unplanned-filter-form" value="{{ request('arrival_from') }}">
                                            <input type="hidden" name="arrival_to" form="unplanned-filter-form" value="{{ request('arrival_to') }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="arrival">Clear</button>
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
                        <td colspan="10" class="st-text-center st-text--muted st-table-empty--compact">No Unplanned Transactions Found</td>
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
        // Initialize single-date range picker for arrival date
        var arrivalRangeInput = document.querySelector('input#unplanned_arrival_range');
        if (arrivalRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
            var fromInput = document.querySelector('input[name="arrival_from"]');
            var toInput = document.querySelector('input[name="arrival_to"]');
            var initial = fromInput && fromInput.value ? fromInput.value : '';
            if (initial) {
                arrivalRangeInput.value = initial;
            }

            window.jQuery(arrivalRangeInput).dateRangePicker({
                autoClose: true,
                singleDate: true,
                showShortcuts: false,
                singleMonth: true,
                format: 'YYYY-MM-DD'
            }).bind('datepicker-change', function(event, obj) {
                var value = (obj && obj.value) ? obj.value : '';
                if (fromInput) fromInput.value = value;
                if (toInput) toInput.value = value;
                arrivalRangeInput.value = value;
                setTimeout(function() {
                    document.getElementById('unplanned-filter-form').submit();
                }, 100);
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
