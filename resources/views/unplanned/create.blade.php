@extends('layouts.app')

@section('title', 'Create Unplanned Transaction - Slot Time Management')
@section('page_title', 'Create Unplanned Transaction')

@section('content')
    @php
        $urlPoSearch = route('slots.ajax.po_search');
        $urlPoDetailTemplate = route('slots.ajax.po_detail', ['poNumber' => '__PO__']);
    @endphp
    <div class="st-card">
        <form method="POST" action="{{ route('unplanned.store') }}" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div class="st-alert st-alert--error">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="st-alert__text">
                        <div style="font-weight:600;margin-bottom:2px;">Validation Error</div>
                        <div style="font-size:12px;">
                            <ul style="margin:0;padding-left:16px;">
                                @foreach ($errors->all() as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">PO/DO Number <span style="color:#dc2626;">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="po_number" name="po_number" maxlength="12" autocomplete="off" class="st-input{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}" required value="{{ old('po_number', old('truck_number')) }}">
                        <div id="po_suggestions" class="st-suggestions st-suggestions--po" style="display:none;"></div>
                        <div id="po_preview" style="margin-top:8px;"></div>
                        <div id="po_items_group" style="display:none;margin-top:10px;"></div>
                    </div>
                    @error('po_number')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                    @error('po_items')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction</label>
                    <select name="direction" id="direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}">
                        <option value="">Choose...</option>
                        <option value="inbound" @if (old('direction') === 'inbound') selected @endif>Inbound</option>
                        <option value="outbound" @if (old('direction') === 'outbound') selected @endif>Outbound</option>
                    </select>
                    @error('direction')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:none;">
                <div class="st-form-field">
                    <label class="st-label">Warehouse</label>
                    <select name="warehouse_id" id="unplanned-warehouse" class="st-select">
                        <option value="">Choose...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Gate (Actual) <span style="color:#dc2626;">*</span></label>
                    <select name="actual_gate_id" id="unplanned-gate" class="st-select{{ $errors->has('actual_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string)old('actual_gate_id') === (string)$gate->id ? 'selected' : '' }}>
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('actual_gate_id')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Arrival Time <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="actual_arrival" id="actual_arrival_input" class="st-input{{ $errors->has('actual_arrival') ? ' st-input--invalid' : '' }}" required value="{{ old('actual_arrival') }}" placeholder="Select Date and Time">
                    @error('actual_arrival')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <label class="st-label" style="font-weight:600;">Queue Status</label>
                <div style="display:flex;gap:12px;align-items:center;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="set_waiting" value="1" {{ old('set_waiting') === '1' ? 'checked' : '' }} style="margin:0;">
                        <span>Set to Waiting</span>
                    </label>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">MAT DOC <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="mat_doc" class="st-input" value="{{ old('mat_doc') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">SJ Number <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="sj_number" class="st-input" value="{{ old('sj_number') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <select name="truck_type" class="st-select">
                        <option value="">-</option>
                        @foreach ($truckTypes as $tt => $label)
                            <option value="{{ $tt }}" {{ old('truck_type') === $tt ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="vehicle_number_snap" class="st-input" value="{{ old('vehicle_number_snap') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                    <input type="text" name="driver_name" class="st-input" value="{{ old('driver_name') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                    <input type="text" name="driver_number" class="st-input" value="{{ old('driver_number') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Notes <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="notes" class="st-input" value="{{ old('notes') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">COA (PDF) <span style="color:#dc2626;">*</span></label>
                    <input type="file" name="coa_pdf" class="st-input{{ $errors->has('coa_pdf') ? ' st-input--invalid' : '' }}" accept="application/pdf" required>
                    @error('coa_pdf')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Surat Jalan (PDF) <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                    <input type="file" name="surat_jalan_pdf" class="st-input{{ $errors->has('surat_jalan_pdf') ? ' st-input--invalid' : '' }}" accept="application/pdf">
                    @error('surat_jalan_pdf')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn">Save</button>
                <a href="{{ route('unplanned.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="old_po_items_json">{!! json_encode(old('po_items', [])) !!}</script>
    <script type="application/json" id="slot_routes_json">{!! json_encode([
        'po_search' => route('slots.ajax.po_search'),
        'po_detail_template' => route('slots.ajax.po_detail', ['poNumber' => '__PO__']),
    ]) !!}</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize routes from JSON
    var routesEl = document.getElementById('slot_routes_json');
    var slotRoutes = {};
    try {
        slotRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
    } catch (e) {
        slotRoutes = {};
    }

    var urlPoSearch = slotRoutes.po_search || '';
    var urlPoDetailTemplate = slotRoutes.po_detail_template || '';

    var oldPoItems = {};
    try {
        var oldPoItemsEl = document.getElementById('old_po_items_json');
        oldPoItems = oldPoItemsEl ? JSON.parse(oldPoItemsEl.textContent || '{}') : {};
    } catch (e) {
        oldPoItems = {};
    }

    // PO/DO autocomplete
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var poPreview = document.getElementById('po_preview');
    var poItemsGroup = document.getElementById('po_items_group');
    var directionSelect = document.getElementById('direction');
    var poDebounceTimer = null;

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function getJson(url) {
        return fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            }
        }).then(function (res) { return res.json(); });
    }

    function clearPoSuggestions() {
        if (!poSuggestions) return;
        poSuggestions.style.display = 'none';
        poSuggestions.innerHTML = '';
    }

    function renderPoSuggestions(items) {
        if (!poSuggestions) return;
        if (!items || !items.length) {
            clearPoSuggestions();
            return;
        }

        poSuggestions.innerHTML = '';
        // Limit to 5 items
        items.slice(0, 5).forEach(function (it) {
            var div = document.createElement('div');
            div.className = 'po-item';
            div.setAttribute('data-po', it.po_number || '');
            div.innerHTML = '<div class="po-item__title">' + (it.po_number || '') + '</div>'
                + '<div class="po-item__sub">' + (it.vendor_name || '') + (it.plant ? (' • ' + it.plant) : '') + '</div>';
            div.style.cssText = 'padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
            poSuggestions.appendChild(div);
        });
        poSuggestions.style.display = 'block';
    }

    function setPoPreview(po) {
        if (poPreview) {
            if (!po) {
                poPreview.textContent = '';
            } else {
                var items = Array.isArray(po.items) ? po.items : [];
                poPreview.innerHTML = ''
                    + '<div class="po-preview__box">'
                    + '<div class="po-preview__title">' + (po.po_number || '') + '</div>'
                    + '<div class="po-preview__info">Vendor: ' + (po.vendor_name || '-') + '</div>'
                    + '<div class="po-preview__info">Plant: ' + (po.plant || '-') + '</div>'
                    + '<div class="po-preview__info">Doc Date: ' + (po.doc_date || '-') + '</div>'
                    + '<div class="po-preview__items">Items: ' + items.length + '</div>'
                    + '</div>';
            }
        }

        if (!poItemsGroup) return;
        poItemsGroup.style.display = 'none';
        poItemsGroup.innerHTML = '';
        if (!po || !Array.isArray(po.items) || po.items.length === 0) {
            return;
        }

        var items = po.items;
        var html = '';
        html += '<div style="font-weight:600;margin-bottom:6px;">PO Items & Quantity for This Unplanned (Optional)</div>';
        html += '<div class="st-table-wrapper" style="margin-top:6px;">';
        html += '<table class="st-table" style="font-size:12px;">';
        html += '<thead><tr>'
            + '<th style="width:70px;">Item</th>'
            + '<th>Material</th>'
            + '<th style="width:120px;text-align:right;">Qty PO</th>'
            + '<th style="width:120px;text-align:right;">Booked</th>'
            + '<th style="width:120px;text-align:right;">Remaining</th>'
            + '<th style="width:160px;">Qty This Unplanned</th>'
            + '</tr></thead><tbody>';

        items.forEach(function (it) {
            if (!it) return;
            var itemNo = String(it.item_no || '').trim();
            if (!itemNo) return;
            var mat = String(it.material || '').trim();
            var desc = String(it.description || '').trim();
            var uom = String(it.uom || '').trim();
            var qtyPo = (it.qty !== undefined && it.qty !== null) ? String(it.qty) : '-';
            var qtyBooked = (it.qty_booked !== undefined && it.qty_booked !== null) ? String(it.qty_booked) : '0';
            var remaining = (it.remaining_qty !== undefined && it.remaining_qty !== null) ? String(it.remaining_qty) : '-';

            var oldQty = '';
            try {
                if (oldPoItems && oldPoItems[itemNo] && oldPoItems[itemNo].qty !== undefined && oldPoItems[itemNo].qty !== null) {
                    oldQty = String(oldPoItems[itemNo].qty);
                }
            } catch (e) {}

            html += '<tr>';
            html += '<td><strong>' + itemNo + '</strong></td>';
            html += '<td>' + mat + (desc ? (' - ' + desc) : '') + '</td>';
            html += '<td style="text-align:right;">' + qtyPo + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td style="text-align:right;">' + qtyBooked + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td style="text-align:right;"><strong>' + remaining + '</strong>' + (uom ? (' ' + uom) : '') + '</td>';
            html += '<td>';
            html += '<input type="number" step="0.001" min="0" name="po_items[' + itemNo + '][qty]" class="st-input" style="max-width:140px;" value="' + (oldQty || '') + '" placeholder="0">';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        html += '<div class="st-text--small st-text--muted" style="margin-top:6px;">Opsional: isi qty jika ingin tercatat sebagai pengiriman untuk PO ini.</div>';

        poItemsGroup.innerHTML = html;
        poItemsGroup.style.display = 'block';
    }

    function fetchPoDetail(poNumber, callback) {
        if (!poNumber) {
            callback({ success: false });
            return;
        }
        getJson(String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber)))
            .then(function (data) {
                if (data && data.success && data.data) {
                    callback({ success: true, data: data.data });
                } else {
                    callback({ success: false });
                }
            })
            .catch(function () { callback({ success: false }); });
    }

    if (poInput) {
        poInput.addEventListener('input', function () {
            var q = (poInput.value || '').trim();
            if (q.length > 10) {
                q = q.slice(0, 10);
                poInput.value = q;
            }

            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(function () {
                getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                    .then(function (data) {
                        if (!data || !data.success) {
                            clearPoSuggestions();
                            return;
                        }
                        renderPoSuggestions(data.data || []);
                    })
                    .catch(function () {
                        clearPoSuggestions();
                    });
            }, 250);
        });

        poInput.addEventListener('focus', function () {
            var q = (poInput.value || '').trim();
            getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                .then(function (data) {
                    if (!data || !data.success) {
                        clearPoSuggestions();
                        return;
                    }
                    renderPoSuggestions(data.data || []);
                })
                .catch(function () {
                    clearPoSuggestions();
                });
        });
    }

    if (poSuggestions) {
        poSuggestions.addEventListener('click', function (e) {
            var item = e.target.closest('.po-item');
            if (!item) return;
            var po = item.getAttribute('data-po');
            if (poInput) poInput.value = po;
            clearPoSuggestions();
            fetchPoDetail(po, function (data) {
                if (data.success && data.data) {
                    setPoPreview(data.data);
                    if (data.data.direction && directionSelect) {
                        directionSelect.value = data.data.direction;
                    }
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        if (!poSuggestions || !poInput) return;
        if (e.target === poInput || poSuggestions.contains(e.target)) return;
        clearPoSuggestions();
    });

    var warehouseSelect = document.getElementById('unplanned-warehouse');
    var gateSelect = document.getElementById('unplanned-gate');
    var arrivalInput = document.getElementById('actual_arrival_input');

    // Initialize flatpickr for arrival time when warehouse is selected
    function initFlatpickrForArrival() {
        if (!arrivalInput) return;
        if (arrivalInput._flatpickr) return;

        // Retry if flatpickr not yet loaded
        if (typeof window.flatpickr !== 'function') {
            setTimeout(initFlatpickrForArrival, 100);
            return;
        }

        var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

        var fp = window.flatpickr(arrivalInput, {
            enableTime: true,
            minDate: "today",
            time_24hr: true,
            allowInput: true,
            disableMobile: true,
            minuteIncrement: 1,
            dateFormat: 'Y-m-d H:i',
            clickOpens: true,
            closeOnSelect: false,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                if (holidayData[dateStr]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = holidayData[dateStr];
                }
            },
            onChange: function (selectedDates, dateStr, instance) {
                try {
                    arrivalInput.dispatchEvent(new Event('input', { bubbles: true }));
                } catch (e) {}
            }
        });
        console.log('Flatpickr initialized for arrival:', fp);
    }

    function syncWarehouseFromGate() {
        if (!warehouseSelect || !gateSelect) return;
        var selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        warehouseSelect.value = selected.getAttribute('data-warehouse-id') || '';
    }

    if (gateSelect) {
        gateSelect.addEventListener('change', function () {
            syncWarehouseFromGate();
            if (arrivalInput) {
                arrivalInput.disabled = !(gateSelect.value || '').trim();
            }
        });
    }
});
</script>
@endpush
