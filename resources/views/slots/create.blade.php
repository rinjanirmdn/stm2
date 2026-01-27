@extends('layouts.app')

@section('title', 'Create Slot - Slot Time Management')
@section('page_title', 'Create Slot')

@section('content')
    <div class="st-card">
        <form method="POST" action="{{ route('slots.store') }}" enctype="multipart/form-data">
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

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">PO/DO Number <span class="st-text--danger-dark">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="po_number" autocomplete="off" name="po_number" class="st-input{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}" required value="{{ old('po_number', old('truck_number')) }}">
                        <div id="po_suggestions" class="st-suggestions st-suggestions--po" style="display:none;"></div>
                        <div id="po_preview" style="margin-top:8px;"></div>
                        <div id="po_items_group" style="display:none;margin-top:10px;"></div>
                    </div>
                    @error('po_number')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                    @error('po_items')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction <span class="st-text--danger-dark">*</span></label>
                    <select name="direction" id="direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Direction...</option>
                        <option value="inbound" {{ old('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ old('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    </select>
                    @error('direction')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                    <select name="truck_type" id="truck_type" class="st-select{{ $errors->has('truck_type') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose...</option>
                        @foreach ($truckTypes as $tt)
                            <option value="{{ $tt }}" {{ old('truck_type') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                    @error('truck_type')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field" style="position:relative;">
                    <label class="st-label">Vendor <span class="st-text--optional">(Optional)</span></label>
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
                        placeholder="Choose Direction First..."
                        style="margin-bottom:4px;"
                        autocomplete="off"
                        {{ old('direction') ? '' : 'disabled' }}
                        value="{{ $oldVendorName }}"
                    >
                    <div id="vendor_suggestions" class="st-suggestions st-suggestions--vendor" style="display:none;"></div>

                    <select name="vendor_id" id="vendor_id" style="display:none;">
                        <option value="">- Optional -</option>
                        @foreach ($vendors as $vendor)
                            <option
                                value="{{ $vendor->id }}"
                                data-type="{{ $vendor->type ?? 'supplier' }}"
                                data-name="{{ strtolower($vendor->name ?? '') }}"
                                {{ (string)old('vendor_id') === (string)$vendor->id ? 'selected' : '' }}
                            >
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field" style="display:none;">
                    <label class="st-label">Warehouse</label>
                    <select name="warehouse_id" id="warehouse_id" class="st-select">
                        <option value="">Choose...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Gate <span class="st-text--danger-dark">*</span></label>
                    <select name="planned_gate_id" id="planned_gate_id" class="st-select{{ $errors->has('planned_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option
                                value="{{ $gate->id }}"
                                data-warehouse-id="{{ $gate->warehouse_id }}"
                                {{ (string)old('planned_gate_id') === (string)$gate->id ? 'selected' : '' }}
                            >
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    <div id="gate_recommendation" class="st-text--small st-text--muted" style="margin-top:2px;"></div>
                    @error('planned_gate_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end;">
                <div class="st-form-field">
                    <label class="st-label">ETA <span class="st-text--danger-dark">*</span></label>
                    <input type="hidden" name="planned_start" id="planned_start_input" value="{{ old('planned_start') }}">
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="planned_start_date_input" class="st-input" placeholder="Select Date" autocomplete="off">
                        <input type="text" id="planned_start_time_input" class="st-input" placeholder="Select Time" autocomplete="off" inputmode="none" readonly>
                    </div>
                    @error('planned_start')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Duration</label>
                    <div style="display:flex;gap:4px;">
                        <input type="number" name="planned_duration" class="st-input{{ $errors->has('planned_duration') ? ' st-input--invalid' : '' }}" value="{{ old('planned_duration', '') }}" min="1" style="flex:1;" id="planned_duration_input">
                        <span class="st-text--small st-text--muted" style="align-self:center;white-space:nowrap;">Min</span>
                    </div>
                    @error('planned_duration')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                    @error('duration_unit')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Risk & Schedule</label>
                    <div style="display:flex;gap:4px;align-items:start;">
                        <div style="flex:1;">
                            <div id="risk_preview" class="st-text--muted" style="font-size:11px;">Risk Not Calculated.</div>
                            <div id="time_warning" class="st-text--small st-text--danger" style="margin-top:2px;"></div>
                        </div>
                        <button type="button" id="btn_schedule_preview" class="st-btn" style="padding:4px 8px;font-size:11px;white-space:nowrap;flex-shrink:0;" {{ old('warehouse_id') ? '' : 'disabled' }}>View Schedule</button>
                    </div>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="vehicle_number_snap" class="st-input{{ $errors->has('vehicle_number_snap') ? ' st-input--invalid' : '' }}" value="{{ old('vehicle_number_snap') }}" placeholder="e.g., B 1234 ABC">
                    @error('vehicle_number_snap')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_name" class="st-input{{ $errors->has('driver_name') ? ' st-input--invalid' : '' }}" value="{{ old('driver_name') }}" placeholder="e.g., Budi">
                    @error('driver_name')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_number" class="st-input{{ $errors->has('driver_number') ? ' st-input--invalid' : '' }}" value="{{ old('driver_number') }}" placeholder="e.g., 08xxxxxxxxxx">
                    @error('driver_number')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">COA (PDF) <span class="st-text--danger-dark">*</span></label>
                    <input type="file" name="coa_pdf" class="st-input{{ $errors->has('coa_pdf') ? ' st-input--invalid' : '' }}" accept="application/pdf" required>
                    @error('coa_pdf')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Surat Jalan (PDF) <span class="st-text--optional">(optional)</span></label>
                    <input type="file" name="surat_jalan_pdf" class="st-input{{ $errors->has('surat_jalan_pdf') ? ' st-input--invalid' : '' }}" accept="application/pdf">
                    @error('surat_jalan_pdf')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="notes" class="st-input{{ $errors->has('notes') ? ' st-input--invalid' : '' }}" value="{{ old('notes') }}" placeholder="Any special notes...">
                    @error('notes')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>


            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn" id="save_button">Save</button>
                <a href="{{ route('slots.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Cancel</a>
            </div>
        </form>
    </div>

    <div id="schedule_modal" class="st-modal">
        <div class="st-modal__content" style="width:600px;max-width:95vw;">
            <div class="st-modal__header">
                <h3 class="st-modal__title">Schedule Preview</h3>
                <button type="button" id="schedule_modal_close" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary); st-btn--sm">Close</button>
            </div>
            <div class="st-modal__body">
                <div id="schedule_modal_info" class="st-modal__info"></div>
                <div style="overflow-x:auto;">
                    <table class="st-table" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>PO/DO</th>
                                <th>Truck</th>
                                <th>Vendor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="schedule_modal_body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="truck_type_durations_json">{!! json_encode($truckTypeDurations ?? []) !!}</script>
    <script type="application/json" id="old_po_items_json">{!! json_encode(old('po_items', [])) !!}</script>
    <script type="application/json" id="slot_routes_json">{!! json_encode([
        'check_risk' => route('slots.ajax.check_risk'),
        'check_slot_time' => route('slots.ajax.check_slot_time'),
        'recommend_gate' => route('slots.ajax.recommend_gate'),
        'schedule_preview' => route('slots.ajax.schedule_preview'),
        'po_search' => route('slots.ajax.po_search'),
        'po_detail_template' => route('slots.ajax.po_detail', ['poNumber' => '__PO__']),
        'vendor_search' => route('api.sap.vendor.search'),
    ]) !!}</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var directionSelect = document.getElementById('direction');
    var truckTypeSelect = document.getElementById('truck_type');
    var poInput = document.getElementById('po_number');
    var poSuggestions = document.getElementById('po_suggestions');
    var poPreview = document.getElementById('po_preview');
    var poItemsGroup = document.getElementById('po_items_group');

    var vendorSearch = document.getElementById('vendor_search');
    var vendorSelect = document.getElementById('vendor_id');
    var vendorSuggestions = document.getElementById('vendor_suggestions');

    var warehouseSelect = document.getElementById('warehouse_id');
    var gateSelect = document.getElementById('planned_gate_id');
    var plannedStartInput = document.getElementById('planned_start_input');
    var plannedStartDateInput = document.getElementById('planned_start_date_input');
    var plannedStartTimeInput = document.getElementById('planned_start_time_input');
    var plannedDurationInput = document.querySelector('input[name="planned_duration"]');
    var durationUnitSelect = document.querySelector('select[name="duration_unit"]');

    var truckTypeDurationsEl = document.getElementById('truck_type_durations_json');
    var truckTypeDurations = {};
    try {
        truckTypeDurations = truckTypeDurationsEl ? JSON.parse(truckTypeDurationsEl.textContent || '{}') : {};
    } catch (e) {
        truckTypeDurations = {};
    }

    var riskPreview = document.getElementById('risk_preview');
    var gateRecommendation = document.getElementById('gate_recommendation');
    var timeWarning = document.getElementById('time_warning');
    var saveButton = document.getElementById('save_button');

    var scheduleModal = document.getElementById('schedule_modal');
    var scheduleModalBody = document.getElementById('schedule_modal_body');
    var scheduleModalInfo = document.getElementById('schedule_modal_info');
    var scheduleModalClose = document.getElementById('schedule_modal_close');
    var schedulePreviewBtn = document.getElementById('btn_schedule_preview');

    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    var uiHasOverlap = false;
    var uiOverlapPending = false;

    function setPlannedStartValue(val) {
        if (!plannedStartInput) return;
        var safe = String(val || '').trim();
        safe = safe.replace('T', ' ');
        plannedStartInput.value = safe;

        if (plannedStartDateInput && plannedStartTimeInput) {
            if (safe) {
                var parts = safe.split(' ');
                plannedStartDateInput.value = parts[0] || '';
                plannedStartTimeInput.value = (parts[1] || '').slice(0, 5);
            } else {
                plannedStartDateInput.value = '';
                plannedStartTimeInput.value = '';
            }
        }
    }

    var uiRiskHigh = false;
    var uiRiskPending = false;

    var routesEl = document.getElementById('slot_routes_json');
    var slotRoutes = {};
    try {
        slotRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
    } catch (e) {
        slotRoutes = {};
    }

    var urlCheckRisk = slotRoutes.check_risk || '';
    var urlCheckSlotTime = slotRoutes.check_slot_time || '';
    var urlRecommendGate = slotRoutes.recommend_gate || '';
    var urlSchedulePreview = slotRoutes.schedule_preview || '';
    var urlPoSearch = slotRoutes.po_search || '';
    var urlPoDetailTemplate = slotRoutes.po_detail_template || '';
    var urlVendorSearch = slotRoutes.vendor_search || '';

    var oldPoItems = {};
    try {
        var oldPoItemsEl = document.getElementById('old_po_items_json');
        oldPoItems = oldPoItemsEl ? JSON.parse(oldPoItemsEl.textContent || '{}') : {};
    } catch (e) {
        oldPoItems = {};
    }

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function postJson(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        }).then(function (res) { return res.json(); });
    }

    function getJson(url) {
        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (res) { return res.json(); });
    }

    function applySaveState() {
        if (!saveButton) return;
        saveButton.disabled = !!(uiOverlapPending || uiHasOverlap || uiRiskPending || uiRiskHigh);
    }

    function updateDurationFromTruckType() {
        if (!truckTypeSelect || !plannedDurationInput) return;
        var tt = (truckTypeSelect.value || '').trim();
        var minutes = tt && truckTypeDurations && truckTypeDurations[tt] ? parseInt(truckTypeDurations[tt], 10) : NaN;

        if (!tt || !isFinite(minutes) || minutes <= 0) {
            // If truck type is not in the database, allow manual input
            plannedDurationInput.removeAttribute('readonly');
            plannedDurationInput.value = plannedDurationInput.value || '';
        } else {
            // If truck type is in the database, autofill and set to readonly
            plannedDurationInput.setAttribute('readonly', 'readonly');
            plannedDurationInput.value = String(minutes);
        }

        if (durationUnitSelect) {
            durationUnitSelect.value = 'minutes';
        }
        updateRiskPreview();
        updateGateRecommendation();
        checkTimeOverlap();
        applySaveState();
    }

    function closePoSuggestions() {
        if (!poSuggestions) return;
        poSuggestions.style.display = 'none';
        poSuggestions.innerHTML = '';
    }

    function renderPoSuggestions(items) {
        if (!poSuggestions) return;
        if (!items || !items.length) {
            closePoSuggestions();
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
        if (!poPreview) return;
        if (poItemsGroup) {
            poItemsGroup.style.display = 'none';
            poItemsGroup.innerHTML = '';
        }
        if (!po) {
            poPreview.textContent = 'No PO/DO Data Yet.';
            return;
        }

        var items = Array.isArray(po.items) ? po.items : [];
        poPreview.innerHTML = ''
            + '<div class="po-preview__box">'
            + '<div class="po-preview__title">' + (po.po_number || '') + '</div>'
            + '<div class="po-preview__info">Vendor: ' + (po.vendor_name || '-') + '</div>'
            + '<div class="po-preview__info">Plant: ' + (po.plant || '-') + '</div>'
            + '<div class="po-preview__info">Doc Date: ' + (po.doc_date || '-') + '</div>'
            + '<div class="po-preview__items">Items: ' + items.length + '</div>'
            + '</div>';

        if (!poItemsGroup) return;
        if (!items.length) {
            poItemsGroup.style.display = 'none';
            poItemsGroup.innerHTML = '';
            return;
        }

        var html = '';
        html += '<div style="font-weight:600;margin-bottom:6px;">PO Items & Quantity for This Slot <span class="st-text--danger-dark">*</span></div>';
        html += '<div class="st-table-wrapper" style="margin-top:6px;">';
        html += '<table class="st-table" style="font-size:12px;">';
        html += '<thead><tr>'
            + '<th style="width:70px;">Item</th>'
            + '<th>Material</th>'
            + '<th style="width:120px;text-align:right;">Qty PO</th>'
            + '<th style="width:120px;text-align:right;">Booked</th>'
            + '<th style="width:120px;text-align:right;">Remaining</th>'
            + '<th style="width:160px;">Qty This Slot</th>'
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
        html += '<div class="st-text--small st-text--muted" style="margin-top:6px;">Input quantity for this slot delivery. Remaining qty remains available for the next slot.</div>';

        poItemsGroup.innerHTML = html;
        poItemsGroup.style.display = 'block';
    }

    function fetchPoDetail(poNumber) {
        console.log('fetchPoDetail called with:', poNumber);
        if (!poNumber) {
            setPoPreview(null);
            return;
        }

        var url = String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber));
        console.log('Fetching PO detail from:', url);

        getJson(url)
            .then(function (data) {
                console.log('PO Detail API Response:', data);
                if (!data || !data.success) {
                    console.warn('PO Detail failed or not found');
                    setPoPreview(null);
                    return;
                }

                console.log('PO Data:', data.data);
                setPoPreview(data.data);

                // Autofill Vendor Name
                if (data.data && data.data.vendor_name) {
                    var vendorSearchInput = document.getElementById('vendor_search');
                    console.log('Vendor search input:', vendorSearchInput);
                    console.log('Setting vendor name to:', data.data.vendor_name);
                    if (vendorSearchInput) {
                        vendorSearchInput.value = data.data.vendor_name;
                        vendorSearchInput.disabled = false;
                        console.log('Vendor name set successfully');
                    }
                }

                // Autofill Direction
                if (data.data && directionSelect) {
                    var vtype = (data.data.vendor_type || '').toLowerCase();
                    var newDir = '';

                    console.log('Vendor type:', vtype);
                    console.log('Direction from API:', data.data.direction);

                    // Use direction from backend if available
                    if (data.data.direction) {
                        newDir = data.data.direction;
                    } else if (vtype === 'supplier') {
                        newDir = 'inbound';
                    } else if (vtype === 'customer') {
                        newDir = 'outbound';
                    }

                    console.log('New direction:', newDir);

                    if (newDir) {
                        directionSelect.value = newDir;
                        console.log('Direction set to:', directionSelect.value);
                        // Trigger change event to update UI states
                        try {
                            directionSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Direction change event dispatched');
                        } catch (e) {
                            console.error('Failed to dispatch change event:', e);
                        }
                        onDirectionChanged();
                    }
                }
            })
            .catch(function (err) {
                console.error('Error fetching PO detail:', err);
                setPoPreview(null);
            });
    }

    function normalizeVendorText(v) {
        return (v || '').replace(/\s+/g, ' ').trim();
    }

    function filterVendors() {
        if (!vendorSelect || !vendorSearch || !vendorSuggestions) return;

        var dir = directionSelect ? directionSelect.value : '';

        if (!dir) {
            vendorSearch.value = '';
            vendorSearch.disabled = true;
            vendorSearch.placeholder = 'Choose Direction First...';
            vendorSelect.value = '';
            clearVendorSuggestions();
            return;
        }

        vendorSearch.disabled = false;
        vendorSearch.placeholder = dir === 'outbound' ? 'Search Customer (SAP)...' : 'Search Supplier (SAP)...';

        var q = (vendorSearch.value || '').trim();
        // Don't search if query is empty, unless we want to show recent/all (maybe too heavy)
        if (q.length < 2) {
            clearVendorSuggestions();
            return;
        }

        var requiredType = dir === 'outbound' ? 'customer' : 'supplier';

        // Debounce AJAX request
        if (vendorDebounceTimer) clearTimeout(vendorDebounceTimer);

        vendorDebounceTimer = setTimeout(function () {
            var finalUrl = String(urlVendorSearch || '') + '?q=' + encodeURIComponent(q) + '&type=' + encodeURIComponent(requiredType);

            // Show loading indicator?
            vendorSuggestions.innerHTML = '<div class="st-suggestion-empty">Searching SAP...</div>';
            vendorSuggestions.style.display = 'block';

            getJson(finalUrl)
                .then(function (data) {
                    if (!data || !data.success || !data.data || data.data.length === 0) {
                        vendorSuggestions.innerHTML = '<div class="st-suggestion-empty">No Vendors Found in SAP/Local</div>';
                        return;
                    }

                    var html = '';
                    data.data.forEach(function (item) {
                        var safeName = (item.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var safeCode = (item.code || '');
                        var sourceBadge = (item.source === 'sap') ? '<span class="st-badge st-badge--info" style="font-size:9px;margin-left:4px;">SAP</span>' : '';

                        html += '<div class="vendor-suggestion-item" data-id="' + item.id + '" data-name="' + safeName + '" data-code="' + safeCode + '">'
                             + '<div>' + safeName + sourceBadge + '</div>'
                             + '<div style="font-size:10px;color:#6b7280;">' + safeCode + '</div>'
                             + '</div>';
                    });

                    vendorSuggestions.innerHTML = html;
                    vendorSuggestions.style.display = 'block';
                })
                .catch(function () {
                    vendorSuggestions.innerHTML = '<div class="st-suggestion-empty">Error Searching Vendor</div>';
                });
        }, 300);
    }

    function onDirectionChanged() {
        var dir = directionSelect ? (directionSelect.value || '') : '';
        if (vendorSelect) vendorSelect.value = '';
        if (vendorSearch) vendorSearch.value = '';
        clearVendorSuggestions();

        if (!vendorSearch) return;
        if (!dir) {
            vendorSearch.disabled = true;
            vendorSearch.placeholder = 'Choose Direction First...';
            return;
        }

        vendorSearch.disabled = false;
        vendorSearch.placeholder = 'Search Vendor...';
    }

    function syncWarehouseFromGate() {
        if (!warehouseSelect || !gateSelect) return;
        var selected = gateSelect.options[gateSelect.selectedIndex];
        if (!selected) return;
        var wh = selected.getAttribute('data-warehouse-id') || '';
        warehouseSelect.value = wh;
    }

    function applyDatepickerTooltips(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);

        dp.find('td.is-holiday').each(function() {
            const cell = window.jQuery(this);
            const dayText = cell.find('a, span').first().text();
            if (!dayText) return;
            const fallbackYear = inst.drawYear ?? inst.selectedYear;
            const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
            const year = cell.data('year') ?? fallbackYear;
            const month = cell.data('month') ?? fallbackMonth;
            if (year === undefined || month === undefined) return;
            const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
            const title = holidayData[ds] || '';
            if (title) {
                cell.attr('data-st-tooltip', title);
                cell.find('a, span').attr('data-st-tooltip', title);
            }
            cell.removeAttr('title');
            cell.find('a, span').removeAttr('title');
        });
    }

    function bindDatepickerHover(inst) {
        if (!inst || !inst.dpDiv) return;
        const dp = window.jQuery(inst.dpDiv);
        let hideTimer = null;
        let tooltip = document.getElementById('st-datepicker-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'st-datepicker-tooltip';
            tooltip.className = 'st-datepicker-tooltip';
            document.body.appendChild(tooltip);
        }

        dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
        dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
            const text = window.jQuery(this).attr('data-st-tooltip') || '';
            if (!text) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            tooltip.textContent = text;
            tooltip.classList.add('st-datepicker-tooltip--visible');
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
            tooltip.style.left = `${event.clientX + 12}px`;
            tooltip.style.top = `${event.clientY + 12}px`;
        });
        dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
            hideTimer = setTimeout(function() {
                tooltip.classList.remove('st-datepicker-tooltip--visible');
            }, 300);
        });
    }

    function initEtaDatepicker() {
        if (!plannedStartDateInput) return;
        if (plannedStartDateInput.getAttribute('data-st-datepicker') === '1') return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
        plannedStartDateInput.setAttribute('data-st-datepicker', '1');

        window.jQuery(plannedStartDateInput).datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            beforeShowDay: function(date) {
                var ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                if (holidayData[ds]) {
                    return [true, 'is-holiday', holidayData[ds]];
                }
                return [true, '', ''];
            },
            beforeShow: function(input, inst) {
                setTimeout(function() {
                    applyDatepickerTooltips(inst);
                    bindDatepickerHover(inst);
                }, 0);
            },
            onChangeMonthYear: function(year, month, inst) {
                setTimeout(function() {
                    applyDatepickerTooltips(inst);
                    bindDatepickerHover(inst);
                }, 0);
            },
            onSelect: function() {
                syncPlannedStart();
                updateRiskPreview();
                updateGateRecommendation();
                checkTimeOverlap();
                window.jQuery(plannedStartDateInput).datepicker('hide');
            }
        });
    }

    function initEtaTimepicker() {
        if (!plannedStartTimeInput) return;
        if (plannedStartTimeInput.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedStartTimeInput.setAttribute('data-st-timepicker', '1');

        plannedStartTimeInput.addEventListener('keydown', function (event) { event.preventDefault(); });
        plannedStartTimeInput.addEventListener('paste', function (event) { event.preventDefault(); });

        window.mdtimepicker('#planned_start_time_input', {
            format: 'hh:mm',
            is24hour: true,
            theme: 'cyan',
            hourPadding: true
        });

        plannedStartTimeInput.addEventListener('change', function () {
            syncPlannedStart();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    function syncPlannedStart() {
        if (!plannedStartInput || !plannedStartDateInput || !plannedStartTimeInput) return;
        var dateVal = (plannedStartDateInput.value || '').trim();
        var timeVal = (plannedStartTimeInput.value || '').trim();
        if (dateVal && timeVal) {
            plannedStartInput.value = dateVal + ' ' + timeVal;
        } else if (dateVal) {
            plannedStartInput.value = dateVal;
        } else {
            plannedStartInput.value = '';
        }
    }

    function applyWarehouseLockState() {
        var hasGate = !!(gateSelect && gateSelect.value);

        if (plannedStartInput) {
            plannedStartInput.disabled = !hasGate;
            if (plannedStartDateInput) plannedStartDateInput.disabled = !hasGate;
            if (plannedStartTimeInput) plannedStartTimeInput.disabled = !hasGate;
            if (hasGate) {
                initEtaDatepicker();
                initEtaTimepicker();
            } else {
                plannedStartInput.value = '';
                if (plannedStartDateInput) plannedStartDateInput.value = '';
                if (plannedStartTimeInput) plannedStartTimeInput.value = '';
            }
        }

        if (schedulePreviewBtn) {
            schedulePreviewBtn.disabled = !hasGate;
        }
    }

    function onGateChanged() {
        syncWarehouseFromGate();
        applyWarehouseLockState();
    }

    function updateRiskPreview() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !durationUnitSelect || !riskPreview) return;

        var whId = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect.value;

        if (!whId || !start || !duration) {
            riskPreview.textContent = 'Risk Not Calculated.';
            uiRiskHigh = false;
            uiRiskPending = false;
            applySaveState();
            return;
        }

        uiRiskPending = true;
        applySaveState();

        var formData = new FormData();
        formData.append('warehouse_id', whId);
        formData.append('planned_gate_id', gateId);
        formData.append('planned_start', start.replace('T', ' ') + ':00');
        formData.append('planned_duration', duration);
        formData.append('duration_unit', unit);

        postJson(urlCheckRisk, formData)
            .then(function (data) {
                if (!data || !data.success) {
                    riskPreview.textContent = 'Risk Cannot Be Calculated.';
                    uiRiskHigh = false;
                    uiRiskPending = false;
                    applySaveState();
                    return;
                }

                var cls = 'small';
                if (data.badge === 'success') cls += ' text-success';
                if (data.badge === 'warning') cls += ' text-warning';
                if (data.badge === 'danger') cls += ' text-danger';

                riskPreview.className = cls;
                riskPreview.textContent = data.label + ' - ' + data.message;

                uiRiskHigh = !!(data.risk_level >= 2);
                uiRiskPending = false;
                applySaveState();
            })
            .catch(function () {
                riskPreview.textContent = 'Risk tidak dapat dihitung.';
                uiRiskHigh = false;
                uiRiskPending = false;
                applySaveState();
            });
    }

    function checkTimeOverlap() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !durationUnitSelect) return;

        var whId = warehouseSelect.value;
        var gateId = gateSelect ? gateSelect.value : '';
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect.value;

        if (!whId || !start || !duration) {
            if (timeWarning) timeWarning.textContent = '';
            uiHasOverlap = false;
            uiOverlapPending = false;
            applySaveState();
            return;
        }

        uiOverlapPending = true;
        applySaveState();

        var formData = new FormData();
        formData.append('warehouse_id', whId);
        formData.append('planned_gate_id', gateId);
        formData.append('planned_start', start.replace('T', ' ') + ':00');
        formData.append('planned_duration', duration);
        formData.append('duration_unit', unit);

        postJson(urlCheckSlotTime, formData)
            .then(function (data) {
                if (!data || !data.success) {
                    if (timeWarning) timeWarning.textContent = '';
                    uiHasOverlap = false;
                    uiOverlapPending = false;
                    applySaveState();
                    return;
                }

                if (data.overlap) {
                    var msg = data.message ? String(data.message) : 'Waktu Ini Bentrok dengan Slot Lain pada Gate Ini.';
                    if (data.suggested_start) {
                        msg += ' Waktu Otomatis Disesuaikan ke Setelah ' + data.suggested_start + '.';
                        setPlannedStartValue(String(data.suggested_start));
                        if (timeWarning) timeWarning.textContent = msg;
                        updateRiskPreview();
                        updateGateRecommendation();

                        uiHasOverlap = true;
                        uiOverlapPending = false;
                        applySaveState();

                        setTimeout(function () { checkTimeOverlap(); }, 0);
                        return;
                    }

                    if (timeWarning) timeWarning.textContent = msg;
                    uiHasOverlap = true;
                    uiOverlapPending = false;
                    applySaveState();
                } else {
                    if (timeWarning) timeWarning.textContent = '';
                    uiHasOverlap = false;
                    uiOverlapPending = false;
                    applySaveState();
                }
            })
            .catch(function () {
                if (timeWarning) timeWarning.textContent = '';
                uiHasOverlap = false;
                uiOverlapPending = false;
                applySaveState();
            });
    }

    function updateGateRecommendation() {
        if (!warehouseSelect || !plannedStartInput || !plannedDurationInput || !durationUnitSelect || !gateRecommendation) return;

        var whId = warehouseSelect.value;
        var start = plannedStartInput.value;
        var duration = plannedDurationInput.value;
        var unit = durationUnitSelect.value;

        if (!whId || !start || !duration) {
            gateRecommendation.textContent = '';
            return;
        }

        var formData = new FormData();
        formData.append('warehouse_id', whId);
        formData.append('planned_start', start.replace('T', ' ') + ':00');
        formData.append('planned_duration', duration);
        formData.append('duration_unit', unit);

        postJson(urlRecommendGate, formData)
            .then(function (data) {
                if (!data || !data.success) {
                    gateRecommendation.textContent = '';
                    return;
                }

                var recId = (data.gate_id !== undefined && data.gate_id !== null) ? String(data.gate_id) : '';
                var selectedId = gateSelect && gateSelect.value ? String(gateSelect.value) : '';
                if (selectedId !== '' && recId !== '' && selectedId === recId) {
                    gateRecommendation.textContent = '';
                    return;
                }

                var recText = 'Recommended: ' + data.gate_label + ' (' + data.risk_label + ' risk)';
                if (data.note) {
                    recText += ' - ' + String(data.note);
                }
                gateRecommendation.textContent = recText;
            })
            .catch(function () {
                gateRecommendation.textContent = '';
            });
    }

    function formatDateTimeForDisplay(str) {
        if (!str) return '-';
        return str.replace('T', ' ');
    }

    function openScheduleModal() {
        if (!scheduleModal || !scheduleModalBody || !warehouseSelect) return;

        var whId = warehouseSelect.value;
        if (!whId) {
            alert('Pilih Warehouse Terlebih Dahulu.');
            return;
        }

        var gateId = gateSelect ? gateSelect.value : '';

        var dateStr = '';
        if (plannedStartInput && plannedStartInput.value) {
            dateStr = plannedStartInput.value.split(/\s|T/)[0];
        } else {
            dateStr = new Date().toISOString().slice(0, 10);
        }

        scheduleModalBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:10px;" class="st-text--muted">Loading...</td></tr>';
        if (scheduleModalInfo) {
            scheduleModalInfo.textContent = 'Warehouse ID ' + whId + (gateId ? (', Gate ID ' + gateId) : '') + ' | Date ' + dateStr;
        }

        var fd = new FormData();
        fd.append('warehouse_id', whId);
        fd.append('planned_gate_id', gateId);
        fd.append('date', dateStr);

        postJson(urlSchedulePreview, fd)
            .then(function (data) {
                if (!data || !data.success) {
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:10px;" class="st-text--danger">Gagal memuat jadwal</td></tr>';
                    scheduleModalInfo.textContent = '';
                    return;
                }

                var items = data.items || [];
                if (items.length === 0) {
                    scheduleModalBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:10px;" class="st-text--muted">Tidak ada slot scheduled / in progress pada tanggal ini.</td></tr>';
                    return;
                }

                var html = '';
                items.forEach(function (item, idx) {
                    var start = formatDateTimeForDisplay(item.planned_start);
                    var finish = formatDateTimeForDisplay(item.planned_finish);
                    var gate = item.gate || '-';
                    var status = (item.status || '').replace('_', ' ');
                    var safeStatus = status.charAt(0).toUpperCase() + status.slice(1);

                    html += '<tr class="schedule-row" data-start="' + (item.planned_start || '') + '" style="cursor:pointer;">';
                    html += '<td>' + (idx + 1) + '</td>';
                    html += '<td>' + start + '</td>';
                    html += '<td>' + (finish || '-') + '</td>';
                    html += '<td>' + gate + '</td>';
                    html += '<td>' + safeStatus + '</td>';
                    html += '</tr>';
                });

                scheduleModalBody.innerHTML = html;
            })
            .catch(function () {
                scheduleModalBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:10px;" class="st-text--danger">Gagal memuat jadwal</td></tr>';
            });

        scheduleModal.style.display = 'flex';
    }

    if (directionSelect) {
        directionSelect.addEventListener('change', function () {
            onDirectionChanged();
            filterVendors();
        });
    }
    if (vendorSearch) {
        vendorSearch.addEventListener('input', filterVendors);
        vendorSearch.addEventListener('focus', filterVendors);
    }
    if (vendorSuggestions) {
        vendorSuggestions.addEventListener('click', function (e) {
            var target = e.target.closest('.vendor-suggestion-item');
            if (!target) return;
            var id = target.getAttribute('data-id');
            var name = target.getAttribute('data-name');
            var code = target.getAttribute('data-code') || '';

            if (id && vendorSelect) {
                // Check if option exists, if not add it (handled dynamic sync)
                var exists = vendorSelect.querySelector('option[value="' + id + '"]');
                if (!exists) {
                    var newOpt = document.createElement('option');
                    newOpt.value = id;
                    newOpt.textContent = name;
                    newOpt.setAttribute('selected', 'selected');
                    vendorSelect.appendChild(newOpt);
                }
                vendorSelect.value = id;
            }
            if (vendorSearch && name) {
                vendorSearch.value = normalizeVendorText(name);
                // Optionally show code ?
            }
            clearVendorSuggestions();
        });
    }
    document.addEventListener('click', function (e) {
        if (vendorSearch && vendorSuggestions) {
            var inside = e.target === vendorSearch || e.target.closest('#vendor_suggestions');
            if (!inside) {
                clearVendorSuggestions();
            }
        }
    });

    if (gateSelect) {
        gateSelect.addEventListener('change', function () {
            onGateChanged();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (plannedStartDateInput) {
        plannedStartDateInput.addEventListener('change', function () {
            syncPlannedStart();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (plannedStartTimeInput) {
        plannedStartTimeInput.addEventListener('input', function () {
            syncPlannedStart();
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (plannedStartInput && plannedStartInput.value) {
        setPlannedStartValue(plannedStartInput.value);
    }

    if (plannedDurationInput) {
        plannedDurationInput.addEventListener('change', function () {
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
        plannedDurationInput.addEventListener('keyup', function () {
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (gateSelect && (gateSelect.value || '').trim() !== '') {
        onGateChanged();
    }

    if (durationUnitSelect) {
        durationUnitSelect.addEventListener('change', function () {
            updateRiskPreview();
            updateGateRecommendation();
            checkTimeOverlap();
        });
    }

    if (truckTypeSelect) {
        truckTypeSelect.addEventListener('change', function () {
            updateDurationFromTruckType();
        });
    }

    if (poInput) {
        var poDebounceTimer = null;

        poInput.addEventListener('input', function () {
            var q = (poInput.value || '').trim();
            setPoPreview(null);

            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(function () {
                getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                    .then(function (data) {
                        if (!data || !data.success) {
                            closePoSuggestions();
                            return;
                        }
                        renderPoSuggestions(data.data || []);
                    })
                    .catch(function () {
                        closePoSuggestions();
                    });
            }, 250);
        });

        poInput.addEventListener('focus', function () {
            var q = (poInput.value || '').trim();
            if (q.length < 3) return; // Don't search on focus if empty/short
            getJson(String(urlPoSearch || '') + '?q=' + encodeURIComponent(q))
                .then(function (data) {
                    if (!data || !data.success) {
                        closePoSuggestions();
                        return;
                    }
                    renderPoSuggestions(data.data || []);
                })
                .catch(function () {
                    closePoSuggestions();
                });
        });

        // Auto-fetch detail on blur/change if user typed a full number directly
        function autoFetchDetail() {
            var val = (poInput.value || '').trim();
            if (val.length >= 5) {
                // Delay slightly to avoid conflict with suggestion click
                setTimeout(function() {
                    // Check if we already have data loaded?
                    // Actually fetchPoDetail handles re-fetching safely.
                    fetchPoDetail(val);
                }, 200);
            }
        }
        poInput.addEventListener('change', autoFetchDetail);
        poInput.addEventListener('blur', autoFetchDetail);
    }

    if (poSuggestions) {
        poSuggestions.addEventListener('click', function (e) {
            var item = e.target.closest('.po-item');
            if (!item || !poInput) return;
            var poNumber = item.getAttribute('data-po') || '';
            poInput.value = poNumber;
            closePoSuggestions();
            fetchPoDetail(poNumber);
        });
    }

    document.addEventListener('click', function (e) {
        if (!poSuggestions || !poInput) return;
        if (e.target === poInput || poSuggestions.contains(e.target)) return;
        closePoSuggestions();
    });

    if (poInput && (poInput.value || '').trim() !== '') {
        fetchPoDetail((poInput.value || '').trim());
    }

    updateDurationFromTruckType();

    if (schedulePreviewBtn) {
        schedulePreviewBtn.addEventListener('click', openScheduleModal);
    }

    if (scheduleModal && scheduleModalBody) {
        scheduleModalBody.addEventListener('click', function (e) {
            var row = e.target.closest('.schedule-row');
            if (!row) return;
            var start = row.getAttribute('data-start') || '';
            if (!start) return;

            var parts = start.split(' ');
            if (parts.length >= 2) {
                var datePart = parts[0];
                var timePart = parts[1].slice(0, 5);
                var val = datePart + ' ' + timePart;
                setPlannedStartValue(val);
                updateRiskPreview();
                updateGateRecommendation();
                checkTimeOverlap();
                scheduleModal.style.display = 'none';
            }
        });
    }

    if (scheduleModalClose) {
        scheduleModalClose.addEventListener('click', function () {
            scheduleModal.style.display = 'none';
        });
    }

    if (scheduleModal) {
        scheduleModal.addEventListener('click', function (e) {
            if (e.target === scheduleModal) {
                scheduleModal.style.display = 'none';
            }
        });
    }

    filterVendors();
    filterGates();
    applyWarehouseLockState();
    updateRiskPreview();
    updateGateRecommendation();
    checkTimeOverlap();

    applySaveState();
});
</script>
@endpush
