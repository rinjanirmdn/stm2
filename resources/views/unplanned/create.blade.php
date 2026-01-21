@extends('layouts.app')

@section('title', 'Create Unplanned Transaction - Slot Time Management')
@section('page_title', 'Create Unplanned Transaction')

@section('content')
    @php
        $urlPoSearch = route('slots.ajax.po_search');
        $urlPoDetailTemplate = route('slots.ajax.po_detail', ['poNumber' => '__PO__']);
    @endphp
    <div class="st-card">
        <form method="POST" action="{{ route('unplanned.store') }}">
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
                    </div>
                    @error('po_number')
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

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field" style="position:relative;">
                    <label class="st-label">Vendor <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    @php
                        $oldVendorName = '';
                        $oldVendorId = old('vendor_id');
                        if ($oldVendorId !== null && (string) $oldVendorId !== '') {
                            foreach ($vendors as $v) {
                                if ((string) ($v->id ?? '') === (string) $oldVendorId) {
                                    $oldVendorName = (string) ($v->name ?? '');
                                    break;
                                }
                            }
                        }
                    @endphp
                    <input
                        type="text"
                        id="vendor_search"
                        class="st-input{{ $errors->has('vendor_id') ? ' st-input--invalid' : '' }}"
                        placeholder="Pilih Direction Dulu..."
                        style="margin-bottom:4px;"
                        autocomplete="off"
                        {{ old('direction') ? '' : 'disabled' }}
                        value="{{ $oldVendorName }}"
                    >
                    <div id="vendor_suggestions" class="st-suggestions st-suggestions--vendor" style="display:none;"></div>
                    <select name="vendor_id" id="vendor_id" style="display:none;">
                        <option value="">-</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" data-direction="{{ $v->type ?? '' }}" {{ (string)old('vendor_id') === (string)$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Warehouse <span style="color:#dc2626;">*</span></label>
                    <select name="warehouse_id" id="unplanned-warehouse" class="st-select{{ $errors->has('warehouse_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Gate (Actual) <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <select name="actual_gate_id" id="unplanned-gate" class="st-select{{ $errors->has('actual_gate_id') ? ' st-input--invalid' : '' }}" {{ old('warehouse_id') ? '' : 'disabled' }}>
                        <option value="">- Optional -</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string)old('actual_gate_id') === (string)$gate->id ? 'selected' : '' }}>
                                {{ $gate->warehouse_name }} - {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('actual_gate_id')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Arrival Time <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="actual_arrival" id="actual_arrival_input" class="st-input{{ $errors->has('actual_arrival') ? ' st-input--invalid' : '' }}" required {{ old('warehouse_id') ? '' : 'disabled' }} value="{{ old('actual_arrival') }}" placeholder="Select Date and Time">
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
                    <label class="st-label">Driver Number <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="driver_number" class="st-input" value="{{ old('driver_number') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Notes <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="notes" class="st-input" value="{{ old('notes') }}">
                </div>
            </div>

            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn">Save</button>
                <a href="{{ route('unplanned.index') }}" class="st-btn st-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

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

    // PO/DO autocomplete
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var vendorSelect = document.getElementById('vendor_id');
    var vendorSearch = document.getElementById('vendor_search');
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

    function fetchPoDetail(poNumber, callback) {
        fetch("{{ route('slots.ajax.po_search') }}?q=" + encodeURIComponent(poNumber))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.data && data.data.length > 0) {
                    callback({ success: true, data: data.data[0] });
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
                    if (data.data.vendor_id && vendorSelect) {
                        vendorSelect.value = String(data.data.vendor_id);
                    }
                    if (data.data.vendor_name && vendorSearch) {
                        vendorSearch.value = data.data.vendor_name;
                    }
                    if (data.data.direction && directionSelect) {
                        directionSelect.value = data.data.direction;
                        // Trigger vendor filter refresh
                        if (typeof filterVendors === 'function') filterVendors();
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

    // Vendor autocomplete
    var vendorSuggestions = document.getElementById('vendor_suggestions');
    var warehouseSelect = document.getElementById('unplanned-warehouse');
    var gateSelect = document.getElementById('unplanned-gate');
    var arrivalInput = document.getElementById('actual_arrival_input');

    function clearVendorSuggestions() {
        if (!vendorSuggestions) return;
        vendorSuggestions.style.display = 'none';
        vendorSuggestions.innerHTML = '';
    }

    function filterVendors() {
        if (!vendorSelect || !vendorSearch) return;
        var dir = (directionSelect ? directionSelect.value : '').toLowerCase();
        var options = vendorSelect.querySelectorAll('option');
        var matched = null;
        var firstMatch = null;
        options.forEach(function (opt) {
            var optDir = (opt.getAttribute('data-direction') || '').toLowerCase();
            if (dir === '' || optDir === dir || optDir === '') {
                opt.style.display = '';
                if (!firstMatch) firstMatch = opt.value;
                if (opt.value === vendorSelect.value) matched = opt.value;
            } else {
                opt.style.display = 'none';
            }
        });
        if (matched === null && firstMatch && vendorSelect.value === '') {
            vendorSelect.value = firstMatch;
        }
    }

    function fetchVendorSuggestions() {
        if (!vendorSearch || !vendorSuggestions || !vendorSelect) return;
        var q = (vendorSearch.value || '').trim();
        var dir = (directionSelect ? directionSelect.value : '').toLowerCase();
        if (q.length < 2) {
            clearVendorSuggestions();
            return;
        }
        var options = vendorSelect.querySelectorAll('option');
        var html = '';
        options.forEach(function (opt) {
            var optDir = (opt.getAttribute('data-direction') || '').toLowerCase();
            if (dir !== '' && optDir !== '' && optDir !== dir) return;
            var name = (opt.textContent || '').trim();
            if (name.toLowerCase().indexOf(q.toLowerCase()) !== -1) {
                html += '<div data-vendor-id="' + opt.value + '" style="padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;">' + name + '</div>';
            }
        });
        if (html === '') {
            vendorSuggestions.innerHTML = '<div style="padding:6px 8px;color:#6b7280;">No Vendor Found</div>';
        } else {
            vendorSuggestions.innerHTML = html;
        }
        vendorSuggestions.style.display = 'block';
    }

    if (directionSelect) {
        directionSelect.addEventListener('change', function () {
            if (vendorSearch) {
                vendorSearch.disabled = false;
                vendorSearch.placeholder = 'Type Vendor Name...';
                vendorSearch.value = '';
                vendorSelect.value = '';
            }
            filterVendors();
        });
    }

    if (vendorSearch) {
        vendorSearch.addEventListener('input', fetchVendorSuggestions);
        vendorSearch.addEventListener('focus', fetchVendorSuggestions);
    }

    if (vendorSuggestions) {
        vendorSuggestions.addEventListener('click', function (e) {
            var item = e.target;
            if (item.tagName === 'DIV' && item.getAttribute('data-vendor-id')) {
                var vid = item.getAttribute('data-vendor-id');
                vendorSelect.value = vid;
                vendorSearch.value = item.textContent;
                clearVendorSuggestions();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!vendorSuggestions || !vendorSearch) return;
        if (e.target === vendorSearch || vendorSuggestions.contains(e.target)) return;
        clearVendorSuggestions();
    });

    // Initialize flatpickr for arrival time when warehouse is selected
    function initFlatpickrForArrival() {
        if (!arrivalInput) return;
        if (arrivalInput._flatpickr) return;
        
        // Retry if flatpickr not yet loaded
        if (typeof window.flatpickr !== 'function') {
            setTimeout(initFlatpickrForArrival, 100);
            return;
        }

        var fp = window.flatpickr(arrivalInput, {
            enableTime: true,
            time_24hr: true,
            allowInput: true,
            disableMobile: true,
            minuteIncrement: 1,
            dateFormat: 'Y-m-d H:i',
            clickOpens: true,
            closeOnSelect: false,
            onChange: function (selectedDates, dateStr, instance) {
                try {
                    arrivalInput.dispatchEvent(new Event('input', { bubbles: true }));
                } catch (e) {}
            }
        });
        console.log('Flatpickr initialized for arrival:', fp);
    }

    // Warehouse -> Gate dependency
    if (warehouseSelect && gateSelect) {
        warehouseSelect.addEventListener('change', function () {
            var whId = this.value;
            var options = gateSelect.querySelectorAll('option');
            var hasEnabled = false;
            options.forEach(function (opt) {
                if (opt.value === '') {
                    opt.style.display = '';
                } else {
                    var wh = opt.getAttribute('data-warehouse-id');
                    if (wh === whId) {
                        opt.style.display = '';
                        hasEnabled = true;
                    } else {
                        opt.style.display = 'none';
                    }
                }
            });
            gateSelect.disabled = !hasEnabled;
            if (!hasEnabled) gateSelect.value = '';
            if (arrivalInput) {
                arrivalInput.disabled = !whId;
                if (whId) {
                    initFlatpickrForArrival();
                }
            }
        });
    }
});
</script>
@endpush
